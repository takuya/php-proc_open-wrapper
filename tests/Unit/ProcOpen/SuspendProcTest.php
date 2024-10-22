<?php

namespace Tests\Unit\ProcOpen;

use Exception;
use Tests\TestCase;
use Takuya\ProcOpen\ProcOpen;

class SuspendProcTest extends TestCase {
  
  /**
   * @requires  OS Darwin|Linux
   */
  public function test_suspend_resume_terminate_by_signal () {
    $proc = new ProcOpen( ['bash', '-c', 'sleep 10'] );
    $proc->start();
    $proc->suspend();
    $this->assertTrue( $proc->isSuspended() );
    $proc->resume();
    $this->assertFalse( $proc->isSuspended( true ) );
    $proc->signal( SIGINT );
    usleep( 1000 );
    $this->assertFalse( $proc->isSuspended() );
    $this->assertFalse( $proc->info->running );
  }
  
  public function test_parse_procps_parse_macos_ps () {
    $str =
      'USER     PID  PPID  PGID   SESS JOBC STAT   TT       TIME COMMAND'.PHP_EOL
      .'takuya 84043 95184 84043      0    1 T    s002    0:00.00 sleep 100'.PHP_EOL;
    // メソッドを取り出す。
    $ret = $this->parse_ps( $str );
    $this->assertEquals(
      [
        "USER"    => "takuya",
        "PID"     => "84043",
        "PPID"    => "95184",
        "PGID"    => "84043",
        "SESS"    => "0",
        "JOBC"    => "1",
        "STAT"    => "T",
        "TT"      => "s002",
        "TIME"    => "0:00.00",
        "COMMAND" => "sleep 100",
      ],
      $ret );
  }
  
  protected function parse_ps ( $ps_string ) {
    $class = new \ReflectionClass(ProcOpen::class);
    $method = $class->getMethod('parse_procps');
    $info =$method->invoke(null,$ps_string);
    
    return $info;
  }
  public function test_parse_procps_linix_with_options(){
    $pid = getmypid();
    $pid > 0 || throw new Exception('error');
    $str = `/bin/ps -o pid,tty,stat,time,command -p $pid`;
    $ret = $this->parse_ps( $str );
    $this->assertArrayHasKey('STAT',$ret);
    $this->assertArrayHasKey('PID',$ret);
    $this->assertArrayHasKey('COMMAND',$ret);
    $this->assertEquals($pid,$ret['PID']);
    $this->assertMatchesRegularExpression('/^php/',$ret['COMMAND']);
    $this->assertNotContains('',$ret);
  }
  public function test_check_is_suspend(){
    $class = new \ReflectionClass(ProcOpen::class);
    $method = $class->getMethod('ps_stat');
    $stat_is_suspend =$method->invoke(null,true,getmypid());
    $this->assertEquals(false,$stat_is_suspend);
  }
  
  public function test_parse_procps_parse_linux_ps () {
    $str = 'PID PSR TTY      STAT   TIME COMMAND'.PHP_EOL.'2011488   1 pts/1    T      0:00 sleep 100'.PHP_EOL;
    // メソッドを取り出す。
    $ret = $this->parse_ps( $str );
    $this->assertEquals(
      [
        "PID"     => "2011488",
        "PSR"     => "1",
        "TTY"     => "pts/1",
        "STAT"    => "T",
        "TIME"    => "0:00",
        "COMMAND" => "sleep 100",
      ],
      $ret );
  }
}
