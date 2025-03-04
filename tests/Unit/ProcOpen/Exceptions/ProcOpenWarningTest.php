<?php

namespace Tests\Unit\ProcOpen\Exceptions;

use Tests\TestCase;
use ReflectionClass;
use Takuya\ProcOpen\ProcOpen;
use Takuya\ProcOpen\Exceptions\InvalidStreamException;
use Takuya\ProcOpen\Exceptions\FailedOpenProcessException;
use Takuya\ProcOpen\Exceptions\ResourceIsMemoryException;

class ProcOpenWarningTest extends TestCase {
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
}