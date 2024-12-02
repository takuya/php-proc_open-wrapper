<?php

namespace Takuya\ProcOpen;

use Takuya\ProcOpen\Traits\Suspend;
use Takuya\ProcOpen\Traits\BufferedOutput;
use Takuya\ProcOpen\Exceptions\FailedOpenProcessException;
use Takuya\ProcOpen\Traits\CheckStreamType;
use Takuya\ProcOpen\Traits\CheckCmd;
use Takuya\ProcOpen\Traits\PseudoStream;
use Takuya\ProcOpen\Traits\SyntaxSugar;

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
    } else if ( is_string( $var ) && strlen( $var ) < 100*2 ) {
      // for tiny input, avoid to use  '/tmp' ( avoiding Disk IO cost ).
      $pseudo_pipe = $this->pseudo_pipe();
      fwrite( $pseudo_pipe['w'], $var );
      fclose( $pseudo_pipe['w'] );
      $var = $pseudo_pipe['r'];
    } else {
      $var = $this->string_io( $var );
    }
    //
    $this->fds[self::STDIN] = $var;
  }
  
  public function setStderr ( $res ) {
    $this->checkStreamType($res );
    $this->fds[self::STDERR] = $res;
  }
  
  public function setStdout ( $res ) {
    $this->checkStreamType($res );
    $this->fds[self::STDOUT] = $res;
  }
  
  public function getFd ( $idx ) {
    return $this->buff[$idx]?? $this->fds[$idx];
  }
  
  
  public function start () {
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
  
  public function wait ( callable $callback = null ) {
    if( !is_callable($callback) && $this->io_buffering_enabled){
      $callback = $this->buff_stream();
    }
    while ( $this->info->running ) {
      is_callable( $callback ) && call_user_func( $callback );
      usleep( 1000 );
    }
  }
  
  public function signal ( $sig ) {
    return $this->info->signal( $sig );
  }
  
  
  
}