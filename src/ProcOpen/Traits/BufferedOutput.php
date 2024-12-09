<?php

namespace Takuya\ProcOpen\Traits;

trait BufferedOutput {
  
  protected array $buff;
  protected bool $io_buffering_enabled=false;
  
  public function enableBuffering(): void {
    $this->io_buffering_enabled=true;
  }
  protected function buff_stream( $streams = [], $interval = 20*1000 ):\Closure {
    return function () use ( $streams, $interval ) {
      if( empty($streams) ) {
        $streams = $this->fds;
        unset($streams[0]);
      }
      $streams = array_filter($streams, fn( $st ) => stream_get_meta_data($st)["stream_type"] == 'STDIO');
      array_map(fn($idx)=>$this->buff[$idx] = $this->buff[$idx] ?? $this->temp_io(), array_keys($streams));
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
      array_map(fn( $s ) => rewind($s), array_intersect_key($this->buff,$streams));
      
      return $this->buff;
    };
  }
}