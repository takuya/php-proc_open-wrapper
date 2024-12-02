<?php

namespace Takuya\ProcOpen\Traits;

use Takuya\ProcOpen\Exceptions\InvalidStreamException;
use Takuya\ProcOpen\Exceptions\ResourceIsMemoryException;

trait CheckStreamType {
  protected function checkStreamType ( $st ) {
    // php://memory だと proc_openは動かない
    // php://temp に限る。
    // https://www.php.net/manual/en/wrappers.php.php#:~:text=One%20difference%20between%20the%20two,as%20the%20sys_get_temp_dir()%20function.
    // Some PHP extensions may require a standard IO stream, and may attempt to cast a given stream to a standard IO stream.
    // This cast can fail for memory streams as it requires the C fopencookie() function to be available.
    if ( !is_resource( $st ) ) {
      throw new InvalidStreamException( "type error: arguments is not stream. proc_open must be stream resource." );
    }
    $meta = stream_get_meta_data( $st );
    if ( !empty( $meta['wrapper_type'] ) && $meta['wrapper_type'] == 'PHP' && $meta["stream_type"] == 'MEMORY' ) {
      throw new ResourceIsMemoryException( "type error: 'php://memory' cannot be used in proc_open" );
    }
  }
  
  
}