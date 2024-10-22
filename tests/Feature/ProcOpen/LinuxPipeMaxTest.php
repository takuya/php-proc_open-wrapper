<?php

namespace Tests\Feature\ProcOpen;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;

class LinuxPipeMaxTest extends TestCase {
  public function test_linux_max_pipe_size_will_be_sleeping () {
    if (PHP_OS !== 'Linux' ){$this->assertNull(null);return;}
    
    $proc = new ProcOpen( ['php'], __DIR__, ['SHELL' => 'php'] );
    $proc->setInput(<<<'EOS'
      <?php
      define('LINUX_PIPE_SIZE_MAX',1024*64+1);
      foreach(range(1,LINUX_PIPE_SIZE_MAX) as $i){
        echo 'a';
      }
      EOS);
    $proc->start();
    $proc->wait(function() use($proc) {
      if (ProcOpen::ps_stat($proc->info->pid,'S')){
        fread($proc->getFd(1),1);// つまりを解消すると動くはず
      }
    });
    
    $out= stream_get_contents($proc->getFd(1));
    $this->assertEquals(1024*64,strlen($out));
  }
}