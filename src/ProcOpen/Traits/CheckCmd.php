<?php

namespace Takuya\ProcOpen\Traits;

trait CheckCmd {
  protected function checkCmd($cmd): void {
    if ( !is_array( $cmd ) ) {
      trigger_error( '$cmd: string is risky, `array` is more safe.', E_USER_WARNING );
      $this->cmd = $this->split_cmd( $cmd );
    }
  }
  protected function split_cmd ( string $cmd ) :array {// int will raise invalidException
    $cmd = trim( $cmd );
    return  preg_split( '/\s+/', $cmd );
  }
  
}