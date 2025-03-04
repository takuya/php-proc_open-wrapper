<?php

namespace Takuya\ProcOpen\Traits;
use function Takuya\ProcOpen\split_cmd_str;

trait CheckCmd {
  protected function checkCmd($cmd): void {
    if ( !is_array( $cmd ) ) {
      trigger_error( '$cmd: string is risky, `array` is more safe. use split_cmd_str() instead.', E_USER_WARNING );
      $this->cmd = $this->split_cmd( $cmd );
    }
  }
  protected function split_cmd ( string $cmd ) :array {
    return split_cmd_str($cmd);
  }
}