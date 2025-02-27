<?php

use Takuya\ProcOpen\ProcOpen;

if( ! function_exists('cmd_exists') ) {
  /**
   * @param string $command_name
   * @return bool|string return path of command if success, false if command is not found.
   */
  function cmd_exists(string $command_name):bool|string {
    $proc = new ProcOpen(['which',$command_name]);
    $proc->run();
    $ret=$proc->getOutput();
    return $proc->info->exitcode===0? trim($ret): false;
  }
}