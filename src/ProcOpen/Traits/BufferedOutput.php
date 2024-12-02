<?php

namespace Takuya\ProcOpen\Traits;

trait BufferedOutput {
  
  protected array $buff;
  protected bool $io_buffering_enabled=false;
  
  public function enableBuffering(){
    $this->io_buffering_enabled=true;
  }
  
  protected function buff_stream( $streams = [], $interval = 100 ):\Closure {
    return function () use ( $streams, $interval ) {
      if( empty($streams) ) {
        $streams = $this->fds;
        unset($streams[0]);
      }
      $streams = array_filter($streams, fn( $st ) => stream_get_meta_data($st)["stream_type"] == 'STDIO');
      $this->buff = array_map(fn( $e ) => $this->temp_io(), $streams);
      while( ! empty(array_filter($streams, fn( $s ) => ! feof($s)))) {
        [$r, $w, $e] = [$streams, [], []];
        stream_select($r, $w, $e, 0, $interval);
        foreach ($r as $idx => $stream) {
          stream_set_blocking($stream, false);
          stream_copy_to_stream($stream, $this->buff[$idx]);
          stream_set_blocking($stream, true);
        }
      }
      // ensure wait.
      while($this->info->running) {
        usleep(100);
      }
      array_map(fn( $s ) => rewind($s), $this->buff);
      
      return $this->buff;
    };
  }
}