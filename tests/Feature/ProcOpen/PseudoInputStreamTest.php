<?php

namespace ProcOpen;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;
use function PHPUnit\Framework\assertEquals;

class PseudoInputStreamTest extends TestCase {
  
  public function test_process_input_pseudo_stream_test() {
    $proc = new ProcOpen(['cat']);
    $proc->setInput(str_repeat('a',1024*160));
    //
    $this->assertEquals('generic_socket',stream_get_meta_data($proc->getFd(0))['stream_type']);
    
    $proc->enableBuffering();
    $proc->start();
    $proc->wait();
    
    $this->assertEquals(1024*160,strlen($proc->getOutput()));
    
    
  }
}