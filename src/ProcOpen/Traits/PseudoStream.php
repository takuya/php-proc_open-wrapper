<?php

namespace Takuya\ProcOpen\Traits;

trait PseudoStream {
  protected function pseudo_pipe (): array {
    // `stream_socket_pair` returns PIPE.
    //  The PIPE has also buffering max size limitation as is pipe STDIO.
    [$read, $write] = stream_socket_pair(
      stripos( PHP_OS, 'win' ) === 0 ? STREAM_PF_INET : STREAM_PF_UNIX,
      STREAM_SOCK_STREAM,
      STREAM_IPPROTO_IP
    );
    return ['r' => $read, 'w' => $write];
  }
  
  protected function string_io ( $str ) {
    $sio = fopen( 'php://temp', 'rw' );
    fwrite( $sio, $str );
    rewind( $sio );
    return $sio;
  }
  protected function temp_io($mega_bytes=10,$mode='w+'){
    return fopen('php://temp/maxmemory:'.(1024*1024*$mega_bytes),$mode);
  }
  
}