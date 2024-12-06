<?php

namespace Tests\Unit\ProcOpen;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;

class WaitStdinCloseTest extends TestCase {
  
  public function test_wait_empty_stdin_close () {
    $input = <<<'EOS'
    <?php
      $stdout = fopen( 'php://stdout', 'w' );
      $stderr = fopen( 'php://stderr', 'w' );
      foreach(range(1,100) as $i ){
        fwrite($stdout, sprintf("hello:%10s\n",$i));
        fwrite($stderr, sprintf("error:%10s\n",$i));
        fflush($stdout);
        fflush($stderr);
        usleep(1000);
      }
    EOS;
    $input .= '/** '.str_repeat('a',1024*10).' */';

    $p = new ProcOpen( ['/usr/bin/php'] );
    $p->start();
    $stdin = $p->stdin();
    fwrite($stdin,$input);
    fclose($stdin);
    $p->wait();
    $this->assertEquals( 100, mb_substr_count( $p->getOutput() , 'hello' ) );
    $this->assertEquals( 100, mb_substr_count( $p->getErrout() , 'error' ) );
  }
  public function test_stdin_buffering_and_wait () {
    $input = <<<'EOS'
    <?php
      $stdout = fopen( 'php://stdout', 'w' );
      $stderr = fopen( 'php://stderr', 'w' );
      foreach(range(1,100) as $i ){
        fwrite($stdout, sprintf("hello:%10s\n",$i));
        fwrite($stderr, sprintf("error:%10s\n",$i));
        fflush($stdout);
        fflush($stderr);
        usleep(1000);
      }
    EOS;
    $input .= '/** '.str_repeat('a',1024*10).' */';

    $p = new ProcOpen( ['/usr/bin/php'] );
    $p->enableBuffering();
    $p->setInput($input);
    $p->start();
    $p->wait();
    $this->assertEquals( 100, mb_substr_count( $p->getOutput() , 'hello' ) );
    $this->assertEquals( 100, mb_substr_count( $p->getErrout() , 'error' ) );
  }
  
}