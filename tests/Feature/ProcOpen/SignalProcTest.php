<?php

namespace Tests\Feature\ProcOpen;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;

class SignalProcTest extends TestCase {
  public function test_process_signaled () {
    if ( PHP_OS !== 'Linux' ) {
      $this->assertNull( null );
      return;
    }
    $signals = [SIGHUP, SIGABRT, SIGKILL, SIGTERM, SIGINT];
    $src = <<<'EOS'
      <?php
      $out = fopen('php://stdout','w');
      foreach(range(1, %s ) as $i){
        fwrite($out,'%s'.PHP_EOL);
        fflush($out);
        usleep(100);
      }
      EOS;
    $cnt = 1000*10;
    foreach ( $signals as $SIG ) {
      $proc = new ProcOpen( ['php'], __DIR__, ['SHELL' => 'php'] );
      $key = bin2hex( random_bytes( 10 ) );
      $proc->setInput( sprintf( $src, $cnt, $key )
      );
      $proc->enableBuffering();
      $proc->start();
      usleep( 100 );
      $this->assertTrue( proc_get_status( $proc->info->getProcResource() )['running'] );
      $called_at = microtime( true );
      $proc->wait( function() use ( $proc, $called_at, $SIG ) {
        $duration = microtime( true ) - $called_at;
        if ( $duration > 100*1000/1000/1000 ) {
          $proc->signal( $SIG );
        }
      } );
      
      $this->assertGreaterThan( 0, substr_count( $proc->getOutput(), $key ) );
      $this->assertLessThan( $cnt, substr_count( $proc->getOutput(), $key ) );
      $this->assertEquals( $SIG, $proc->info->termsig );
    }
  }
}