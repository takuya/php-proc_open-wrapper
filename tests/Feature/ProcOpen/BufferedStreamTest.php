<?php

namespace Tests\Feature\ProcOpen;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;

class BufferedStreamTest extends TestCase {
  
  public function test_process_buffered_stream_test() {
    $proc = new ProcOpen(['php']);
    $proc->setInput(
      <<<'EOS'
      <?php
      define('LINUX_PIPE_SIZE_MAX',1024*64+1);
      $err = fopen('php://stderr','w');
      $out = fopen('php://stdout','w');
      foreach(range(1,LINUX_PIPE_SIZE_MAX) as $i){
        fwrite($err,'e');
        fwrite($out,'a');
      }
      EOS);
    $proc->start();
    $proc->enableBuffering();
    $proc->wait();
    $this->assertEquals(1024*64+1,strlen($proc->getOutput()));
    $this->assertEquals(1024*64+1,strlen($proc->getErrout()));
    
  }
}