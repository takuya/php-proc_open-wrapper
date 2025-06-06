<?php

namespace Takuya\ProcOpen;

use Takuya\ProcOpen\Traits\Suspend;
use Takuya\ProcOpen\Traits\BufferedOutput;
use Takuya\ProcOpen\Exceptions\FailedOpenProcessException;
use Takuya\ProcOpen\Traits\CheckStreamType;
use Takuya\ProcOpen\Traits\CheckCmd;
use Takuya\ProcOpen\Traits\PseudoStream;
use Takuya\ProcOpen\Traits\SyntaxSugar;
use Takuya\ProcOpen\Exceptions\ResourceIsMemoryException;

class ProcOpen {
  use CheckStreamType;
  use Suspend;
  use CheckCmd;
  use PseudoStream;
  use SyntaxSugar;
  use BufferedOutput;
  
  public const STDIN = 0;
  public const STDOUT = 1;
  public const STDERR = 2;
  public ProcInfo $info;
  protected array $fds = [
    // Linux の PIPE_BUF / PIPE_SIZEに影響を受けるので注意。
    // 読み込まずに放置すると詰まるのである。
    self::STDIN  => null,
    self::STDOUT => null,
    self::STDERR => null,
  ];
  
  public function __construct (
    protected $cmd,
    protected $cwd = null,
    protected $env = null,
              $input = null
  
  ) {
    $this->info = new ProcInfo();
    if ( $input ) $this->setInput( $input );
    $this->checkCmd($cmd);
  }
  
  public function setInput ( $var ) {
    if ( is_resource( $var ) ) {
      $this->checkStreamType($var );
    } else if ( $this->can_use_pseudo_pipe($var)) {
      // for tiny input, avoid 'php://temp' ( avoiding Disk IO cost ).
      // linux max size is 1024*160.
      // macOS max size is 1024*8.
      $pseudo_pipe = $this->pseudo_pipe();
      fwrite( $pseudo_pipe['w'], $var );
      fclose( $pseudo_pipe['w'] );
      $var = $pseudo_pipe['r'];
    } else if($this->io_buffering_enabled){
      $this->buff[self::STDIN] = $this->string_io($var);
      $var = null;
    }else {
      $var = $this->string_io( $var );
    }
    //
    $this->fds[self::STDIN] = $var;
  }
  
  public function setStderr ( $res ) {
    try {
      $this->checkStreamType( $res );
      $this->fds[self::STDERR] = $res;
    } catch (ResourceIsMemoryException $e) {
      if ( !$this->io_buffering_enabled ) {
        throw $e;
      }
      $this->buff[self::STDERR] = $this->buff[self::STDERR] ?? $res;
    }
  }
  
  public function setStdout ( $res ) {
    try {
      $this->checkStreamType( $res );
      $this->fds[self::STDOUT] = $res;
    } catch (ResourceIsMemoryException $e) {
      if ( !$this->io_buffering_enabled ) {
        throw $e;
      }
      $this->buff[self::STDOUT] = $this->buff[self::STDOUT] ?? $res;
    }
 }
  
  public function getFd ( $idx ) {
    return $this->buff[$idx]?? $this->fds[$idx];
  }
  protected function openProcPipes(){
    $pipes = [];
    $fds = [
      self::STDIN  => $this->fds[self::STDIN] ?? ['pipe', 'r'],
      self::STDOUT => $this->fds[self::STDOUT] ?? ['pipe', 'w'],
      self::STDERR => $this->fds[self::STDERR] ?? ['pipe', 'w'],
    ];
    $proc_res = proc_open( $this->cmd, $fds, $pipes, $this->cwd, $this->env, null );
    if ( !is_resource( $proc_res ) ) {
      throw new FailedOpenProcessException( 'proc_open Failed.' );
    }
  
    $this->fds[self::STDIN] = $this->fds[self::STDIN] ?? $pipes[self::STDIN];
    $this->fds[self::STDOUT] = $pipes[self::STDOUT] ?? $this->fds[self::STDOUT];
    $this->fds[self::STDERR] = $pipes[self::STDERR] ?? $this->fds[self::STDERR];
    $this->info->setProcResource( $proc_res );
  }
  
  
  public function start () {
    $this->openProcPipes();
  }
  
  public function wait ( callable $callback = null ) {
  
    if ($this->io_buffering_enabled && !empty($this->buff[self::STDIN]) && is_resource($this->buff[self::STDIN])  && is_resource($this->fds[self::STDIN]) && !feof($this->fds[self::STDIN])){
      stream_copy_to_stream($this->buff[self::STDIN],$this->fds[self::STDIN]);
      fclose($this->fds[self::STDIN]);
    }
    if( $this->io_buffering_enabled){
      $wait_with_buffering = $this->buff_stream($callback,20*1000);
      $wait_with_buffering();
    }else{
      while ( $this->info->running ) {
        is_callable( $callback ) && call_user_func( $callback );
        usleep( 1000 );
      }
    }
  }
  
  public function signal ( $sig ) {
    return $this->info->signal( $sig );
  }
  
  
  
}