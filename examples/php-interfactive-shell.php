<?php

use Takuya\ProcOpen\ProcOpen;

/**
 * using interactive shell 'php -a' as line by line stdio.
 */

require __DIR__.'/../vendor/autoload.php';

$proc = new ProcOpen(['php','-a']);
$proc->start();

$fp = $proc->getFd(ProcOpen::STDIN);
fwrite($fp,'echo 0 ;'.PHP_EOL);
fwrite($fp,'echo 1 ;'.PHP_EOL);
fwrite($fp,'echo 2 ;'.PHP_EOL);
fwrite($fp,'echo 3 ;'.PHP_EOL);
fwrite($fp,'echo 4 ;'.PHP_EOL);
fclose($fp); // finish php interactive shell
// get output.
$output= stream_get_contents($proc->getFd(1));
$body = trim( preg_split( '/^$/m', $output )[1] ).PHP_EOL;
echo $body;

