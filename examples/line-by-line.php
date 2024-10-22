<?php


use Takuya\ProcOpen\ProcOpen;

/**
 * using interactive shell 'php -a' as line by line stdio.
 */

require __DIR__.'/../vendor/autoload.php';
$data = [4, 3, 2, 1, 0];

$proc = new ProcOpen( ['php', '-a'], __DIR__, ['SHELL' => 'php'] );
$proc->start();
//
stream_set_blocking( $proc->getFd( ProcOpen::STDOUT ), false );
stream_set_blocking( $proc->getFd( ProcOpen::STDIN ), false );


// line by line execution by 'php -a' ( interactive shell )
$output = '';
$count_stream_selected = 0;
while ( $proc->info->running ) {
  /**
   * Read from process stdout if available
   */
  $avail = stream_select( ...( $selected = [[$proc->getFd( 1 )], [], null, 0, 100] ) );
  if ( $avail > 0 ) {
    $count_stream_selected++;
    $output .= fread( $proc->getFd( 1 ), 1024 );
  }
  /**
   * Write to process stdin if available
   */
  $stdin_not_closed = is_resource( $proc->getFd( 0 ) );
  if ( $stdin_not_closed ) {
    $avail = stream_select( ...( $selected = [[], [$proc->getFd( 0 )], null, 0, 100] ) );
    if ( $avail > 0 ) {
      !empty( $data )
      && fwrite( $proc->getFd( 0 ), 'echo '.array_pop( $data ).'; echo PHP_EOL;'.PHP_EOL )
      && fflush( $proc->getFd( 0 ) );
      empty( $data ) && fclose( $proc->getFd( 0 ) );
    }
  }
  usleep( 100 );
}
// Shaping interactive shell output.
$body = trim( preg_split( '/^$/m', $output )[1] ).PHP_EOL;
echo $body;


