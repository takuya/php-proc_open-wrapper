<?php

namespace Tests\Feature\Helpers;

use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;
use function Takuya\ProcOpen\cmd_exists;
use function Takuya\ProcOpen\split_cmd_str;

class HelperFunctionTest extends TestCase {
  public function test_helper_cmd_exists(){
    $ret = cmd_exists('php');
    $this->assertStringEndsWith('php',$ret);
    $ret = cmd_exists('bash');
    $this->assertStringEndsWith('bash',$ret);
  }
  public function test_helper_split_cmd_str(){
    $ret = split_cmd_str('ln -sr /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled');
    $this->assertCount(4,$ret);
    $this->assertEquals('/etc/nginx/sites-enabled',$ret[3]);
    $ret = split_cmd_str('bash -c "echo 1;"');
    $this->assertCount(3,$ret);
    $this->assertEquals('"echo 1;"',$ret[2]);
  }
}