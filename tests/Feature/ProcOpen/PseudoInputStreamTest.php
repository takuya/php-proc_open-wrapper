<?php

namespace ProcOpen;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;
use function PHPUnit\Framework\assertEquals;

class PseudoInputStreamTest extends TestCase {
  
  public function test_process_input_pseudo_stream_test() {
    $limit = match(PHP_OS){
      'Darwin'=>1024*8,
      'Linux'=>1024*160,
      default => 1024*2
    };
  
    $proc = new ProcOpen(['cat']);
    $proc->setInput(str_repeat('a',$limit));
    //
    $this->assertEquals('generic_socket',stream_get_meta_data($proc->getFd(0))['stream_type']);
    
    $proc->enableBuffering();
    $proc->start();
    $proc->wait();
    
    $this->assertEquals($limit,strlen($proc->getOutput()));
    
    
  }
}