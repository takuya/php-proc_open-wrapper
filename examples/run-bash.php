<?php


use Takuya\ProcessExec\ProcessExecutor;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcOpen\ProcOpen;

require __DIR__.'/../vendor/autoload.php';

$proc = new ProcOpen(['bash'],__DIR__,['SHELL'=>'php']);
$proc->setInput('
for i in {0..4}; do
  echo $i
done;
');
$proc->start();
$proc->wait();
//blocking io
echo fread($proc->getFd(1), 1024);
