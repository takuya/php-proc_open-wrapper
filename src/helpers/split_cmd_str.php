<?php

namespace Takuya\ProcOpen;

if( ! function_exists('split_cmd_str') ) {
  /**
   * @param string $command_name
   * @return array
   */
  function split_cmd_str( string $commandline_string ):array {
    $cmd = trim($commandline_string);
    $cmd = preg_split('/(?:\s+)(?=(?:[^\'"]|\'[^\']*\'|"[^"]*")*$)/', $cmd);
    // $cmd = preg_split('/\s+/', $cmd);
    $cmd = array_map('trim',$cmd);
    $cmd = array_filter($cmd);
    return $cmd;
  }
}