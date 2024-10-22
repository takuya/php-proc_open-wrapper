<?php

namespace Takuya\ProcOpen;

/**
 * @property string command
 * @property int    pid
 * @property bool   running
 * @property bool   signaled
 * @property bool   stopped
 * @property int    exitcode
 * @property int    termsig
 * @property int    stopsig
 */
class ProcInfo {
  /** @var resource */
  protected $proc;
  protected $exitcode;
  protected array $cached_status = ['exitcode' => -1, 'signaled' => false];
  
  public function setProcResource ( $proc ): void {
    $this->proc = $proc;
  }
  
  public function getProcResource () {
    return $this->proc;
  }
  
  public function signal ( $sig ): bool {
    return proc_terminate( $this->proc, $sig );
  }
  
  public function __get ( string $name ) {
    if ( in_array( $name, ["command",
      "pid",
      "running",
      "signaled",
      "stopped",
      "exitcode",
      "termsig",
      "stopsig",] ) ) {
      return $this->status( $name );
    }
    return null;
  }
  
  protected function status ( $name ) {
    if ( $this->cached_status['exitcode'] > -1 ) {
      return $this->cached_status[$name];
    }
    if ( $this->cached_status['signaled'] ) {
      return $this->cached_status[$name];
    }
    $arr = proc_get_status( $this->proc );
    $this->cached_status = $arr;
    return $arr[$name];
  }
  
  
}