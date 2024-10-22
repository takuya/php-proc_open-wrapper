<?php

namespace Tests\Unit\ProcOpen\Exceptions;

use Tests\TestCase;
use ReflectionClass;
use Takuya\ProcOpen\ProcOpen;
use Takuya\ProcOpen\Exceptions\InvalidStreamException;
use Takuya\ProcOpen\Exceptions\FailedOpenProcessException;

class ProcOpenExceptionTest extends TestCase {
  public function test_proc_open_invalid_cmd_as_string () {
    $warningTriggered = false;
    
    // カスタムエラーハンドラを設定して警告をキャッチ
    set_error_handler(function ($errno, $errstr) use (&$warningTriggered) {
      if ($errno === E_USER_WARNING) {
        $warningTriggered = true;
      }
      return true; // デフォルトのエラーハンドラをバイパス
    });
    
    $p = new ProcOpen( '/usr/bin/php -v' );
    $p->start();
    $p->wait();
    restore_error_handler();
    //
    $this->assertEquals('/usr/bin/php',$p->info->command);
    $this->assertTrue($warningTriggered);
  }
  
  public function test_proc_open_stdin_invalid_arguments () {
    $this->expectException( InvalidStreamException::class );
    $p = new ProcOpen( ['/usr/bin/php', '-i'] );
    $p->setInput( fopen( "php://memory", 'rw+' ) );
  }
  
  public function test_proc_open_stdout_invalid_arguments () {
    $this->expectException( InvalidStreamException::class );
    $p = new ProcOpen( ['/usr/bin/php', '-i'] );
    $p->setStdout( fopen( "php://memory", 'rw+' ) );
  }
  
  public function test_proc_open_stdout_invalid_argument_messages () {
    $this->expectExceptionMessage( "type error: 'php://memory' cannot be used in proc_open" );
    $p = new ProcOpen( ['/usr/bin/php', '-i'] );
    $p->setStdout( fopen( "php://memory", 'rw+' ) );
  }
  
  public function test_proc_open_stderr_invalid_arguments () {
    $this->expectException( InvalidStreamException::class );
    $p = new ProcOpen( ['/usr/bin/php', '-i'] );
    $p->setStderr( fopen( "php://memory", 'rw+' ) );
  }
  
  public function test_proc_open_stderr_valid_arguments () {
    // 例外が起きないことをテストする
    $p = new ProcOpen( ['/usr/bin/php', '-i'] );
    $p->setStderr( fopen( "php://temp", 'rw+' ) );
    $this->expectNotToPerformAssertions();
  }
  
  public function test_proc_open_stdout_valid_arguments () {
    // 例外が起きないことをテストする
    $p = new ProcOpen( ['/usr/bin/php', '-i'] );
    $p->setStdout( fopen( "php://temp", 'rw+' ) );
    $this->expectNotToPerformAssertions();
  }
  
  public function test_detect_failed_to_proc_open () {
    set_error_handler( function( ...$args ) {
      $this->assertStringContainsString( "proc_open(): Cannot represent a stream of ", $args[1] );
    } );
    $this->expectException( FailedOpenProcessException::class );
    $p = new ProcOpen( [uniqid( 'a' )], '/rand' );
    $reflection = new ReflectionClass( $p );
    $property = $reflection->getProperty( 'fds' );
    $property->setValue( $p, [0 => null, 1 => fopen( "php://memory", 'a' ), 2 => null] );
    $p->start();
  }
}