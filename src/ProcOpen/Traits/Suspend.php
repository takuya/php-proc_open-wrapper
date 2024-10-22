<?php

namespace Takuya\ProcOpen\Traits;

use InvalidArgumentException;
use RuntimeException;

trait Suspend {
  
  protected bool $suspended = false;
  
  public function suspend (): bool {
    $this->suspended = true;
    return $this->signal( SIGSTOP );
  }
  
  public function resume (): bool {
    $this->suspended = false;
    return $this->signal( SIGCONT );
  }
  
  /**
   * Check suspended by using `ps` command.
   * because... , proc_get_status()['stopped'] is not reliable.
   * @return bool
   */
  public function isSuspended ( $use_ps_cmd = false, $pid = null ): bool {
    // ensure
    if ( $this->info->stopped ) {
      return true;
    }
    if ( false === $use_ps_cmd ) {
      return $this->suspended;
    }
    if( !is_resource($this->info->getProcResource())){
      throw new \LogicException( 'before start.' );
    }
    return self::ps_stat($this->info->pid);
  }
  public static function ps_stat($pid, $status='T'): bool {
    /**
     * D    uninterruptible sleep (usually IO)
     * I    Idle kernel thread
     * R    running or runnable (on run queue)
     * S    interruptible sleep (waiting for an event to complete)
     * T    stopped by job control signal
     * t    stopped by debugger during the tracing
     * W    paging (not valid since the 2.6.xx kernel)
     * X    dead (should never be seen)
     * Z    defunct ("zombie") process, terminated but not reaped by its parent
     */
    if ( !is_executable( '/bin/ps' ) ) {
      throw new RuntimeException( '/bin/ps is not found executable, only GNU Linux/BSD supported.' );
    }
    $ps_string = `/bin/ps -o pid,tty,stat,time,command -p {$pid}`;
    if ( 2 > substr_count( $ps_string, PHP_EOL ) ) {
      return false;
    }
    $info = self::parse_procps($ps_string);
    return str_starts_with( $info['STAT'], $status );
    
  }
  private static function parse_procps($str){
    $lines = preg_split( "/\r\n|\n|\r/", $str );
    if ( sizeof( $lines ) < 2 ) {
      throw new InvalidArgumentException();
    }
    [$headers, $body] = array_slice( array_map('trim',$lines), 0, 2 );
    $headers = preg_split( '/\s+/', $headers );
    $body = preg_split( '/\s+/', $body, sizeof( $headers ) );
    $info = array_combine( $headers, $body );
    
    return $info;
  }
}
