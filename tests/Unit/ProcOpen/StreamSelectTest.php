<?php

namespace Tests\Unit\ProcOpen;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;

class StreamSelectTest extends TestCase {
  
  public function test_stream_select_with_proc_open () {
    $p = new ProcOpen( ['php'] );
    $p->setInput(
      <<<'EOS'
    <?php
      $stdout = fopen( 'php://stdout', 'w' );
      foreach(range(1,100) as $i ){
        fwrite($stdout, sprintf("hello:%10s\n",$i));
        fflush($stdout);
        usleep(1000);
      }
    EOS
    );
    $p->start();
    $p->wait( function() use ( $p ) {
      $r = [$p->stderr(), $p->stdout()];
      $ret = stream_select( $r, $w, $e, 0, 10 );
      if ( $ret > 0 ) {
        $line = stream_get_line( $p->stdout(), 1024, "\n" );
        if ( !empty( $line ) ) {
          $this->assertMatchesRegularExpression( '/^hello:\s+\d+$/', $line );
        }
      }
    } );
  }
}