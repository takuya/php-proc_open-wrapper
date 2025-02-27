<?php

namespace Tests\Feature\Helpers;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;

class HelperFunctionTest extends TestCase {
  public function test_helper_cmd_exists(){
    $ret = cmd_exists('php');
    $this->assertStringEndsWith('php',$ret);
    $ret = cmd_exists('bash');
    $this->assertStringEndsWith('bash',$ret);
  }
}