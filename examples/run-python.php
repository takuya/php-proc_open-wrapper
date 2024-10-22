<?php

require_once __DIR__.'/../vendor/autoload.php';

use Takuya\ProcOpen\ProcOpen;

$cmd = ['python','-c','for i in range(5): print(i); '];

$proc = new ProcOpen($cmd,__DIR__,['SHELL'=>'php']);
$proc->start();
$proc->wait();
echo fread($proc->getFd(1), 1024);



