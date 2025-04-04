<?php

namespace Takuya\ProcOpen\Traits;

trait BufferedOutput {
  
  protected array $buff;
  protected bool $io_buffering_enabled=false;
  
  public function enableBuffering(): void {
    $this->io_buffering_enabled=true;
  }
  protected function copy_out_to_buff( $interval_micro_sec = 20*1000 ):\Closure {
    $raw_streams = array_filter($this->fds,fn($s)=>stream_get_meta_data($s)["stream_type"] == 'STDIO');
    $raw_out_streams = array_filter($raw_streams,fn($idx)=>$idx>0,ARRAY_FILTER_USE_KEY);
    array_map(fn($idx)=>$this->buff[$idx] ??= $this->temp_io(),array_keys($raw_out_streams));
    //
    $interval= [intval($interval_micro_sec/1000/1000),intval($interval_micro_sec)];
    return function () use ($raw_out_streams,$interval) {
      [$r, $w, $e] = [$raw_out_streams, [], []];
      if(stream_select($r, $w, $e,$interval[0],$interval[1])){
        foreach ( $r as $idx=> $item ) {
          stream_set_blocking($this->fds[$idx], false);
          stream_copy_to_stream($this->fds[$idx], $this->buff[$idx]);
          stream_set_blocking($this->fds[$idx], true);
        }
      };
      return $this->buff;
    };
  }
  protected function buff_stream( callable $callback=null, $select_interval_micro_sec = 20*1000 ):\Closure {
    return function () use ( $select_interval_micro_sec,$callback ) {
      $stream_copy_to_buffer = $this->copy_out_to_buff($select_interval_micro_sec);
      $callback_with_buffering= function()use($stream_copy_to_buffer,$callback){
          call_user_func($stream_copy_to_buffer);
          is_callable( $callback ) && call_user_func( $callback );
        };
      while ( $this->info->running ) {
        call_user_func( $callback_with_buffering );
        usleep( 1000 );
      }
      $stream_copy_to_buffer();//ensure read remains
      // rewind.
      array_map(fn( $s ) => rewind($s),array_filter($this->buff,fn($idx)=>$idx>0,ARRAY_FILTER_USE_KEY));
      return $this->buff;
    };
  }
}