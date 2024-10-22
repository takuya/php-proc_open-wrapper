<?php

namespace Tests\Example;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;

class RunExamplesTest extends TestCase {
  
  public function test_examples_are_runs_without_errors () {
    $list = array_map(
      'realpath',
      array_merge(
        glob( __DIR__.'/../../examples/*.php' ),
        glob( __DIR__.'/../../examples/*/*.php' ), ) );
    foreach ( $list as $f ) {
      $p = new ProcOpen( ['php', $f ] );
      $p->start();
      $p->wait();
      $this->assertEquals( 0, $p->info->exitcode );
      $this->assertEquals( "0\n1\n2\n3\n4\n",stream_get_contents($p->getFd(1)) );
    }
  }
}