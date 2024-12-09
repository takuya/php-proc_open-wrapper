## Run Process.


Run process and run string by interpreter(ex.python,bash). and keep process long time task running.

`proc_open` is distinctive style. so I make `proc_open` EASY and friendly.


## Installing

from packagist. 
```
composer require takuya/php-proc_open-wrapper
```
from GitHub repos.
```sh
name='php-proc_open-wrapper'
composer config repositories.$name \
vcs https://github.com/takuya/$name  
composer require takuya/$name:master
composer install
```

check installation.

sample.php
```php
<?php
require_once 'vendor/autoload.php';
use Takuya\ProcOpen\ProcOpen;

$p = new ProcOpen( ['php','-v'] );
$p->start();
// This is enough to wait process end, because blocked. 
echo $output = stream_get_contents($p->getFd(1));
```
run.
```shell
php sample.php
```

wait long time process.

```php
<?php
require_once 'vendor/autoload.php';
use Takuya\ProcOpen\ProcOpen;

$p = new ProcOpen( ['sleep','30'] );
$p->start();
$p->wait();
```

## comparison proc_open , ProcOpen

- process (no input)
- process (with stdin,stderr,stdout )
- process (read output)
- process (pipe line)

### simple (no input, no stdout)
proc_open
```php
<?php
$proc = proc_open('rm -rf /tmp/ABcDE',[1=>['pipe','w'],2=>['pipe','w']],$io);

while( ($pstat = proc_get_status($proc))&& $pstat['running'] ){
  usleep(100);
}

```
ProcOpen Wrapper(this package)
```php
<?php
require_once 'vendor/autoload.php';
use Takuya\ProcOpen\ProcOpen;
//
$p = new ProcOpen('rm -rf /tmp/ABcDE');
$p->start();
$p->wait();
```
### simple ( with stdin,stderr,stdout )
proc_open
```php
<?php

$proc = proc_open('grep root',[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$io);
// pass stdin
stream_copy_to_stream(fopen('/etc/passwd','r'),$io[0]);
fclose($io[0]);
// get stderr , stdout
file_put_contents('php://stderr',stream_get_contents($io[2]));
echo stream_get_contents($io[1]);
```
ProcOpen Wrapper (this package)
```php
<?php
use Takuya\ProcOpen\ProcOpen;

$proc = new ProcOpen(['grep','root']);
$proc->setInput(fopen('/etc/passwd','r'));
$proc->start();
file_put_contents('php://stderr',$proc->getErrout());
echo $proc->getOutput();

```
### simple ( read output )
Open php Interactive Shell (`php -a`) and write and read out
```shell
takuya@host :$ php -a
Interactive shell

php > echo 0;
0
php > echo 1;
1
php > echo 2;
2
```
`proc_open` : Do as above in. 
```php
<?php
$proc = proc_open(['php','-a'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $io);
foreach (range(0, 9) as $str) {
  echo fread($io[1],1024);
  fwrite($io[0],"echo {$str};\n");
}
fclose($io[0]);
echo stream_get_contents($io[1]);
```
`ProcOpen` Wrapper
```php
<?php
$proc = new ProcOpen(['php','-a']);
$proc->start();
foreach (range(0, 9) as $str) {
  echo fread($proc->stdout(),1024);
  fwrite($proc->stdin(),"echo {$str};\n");
}
fclose($proc->stdin());
echo $proc->getOutput();
```

### sample pipe(  String and Shell ) 
`proc_open` is accept `string $cmd` but not safe. 
```php
<?php
# shell calling, Not Safe.
$proc = proc_open('cat /etc/passwd | grep root',[1=>['pipe','w']],$io);
echo stream_get_contents($io[1]);
# pipe io , More Safe.
$p1 = proc_open('cat /etc/passwd',[1=>['pipe','w']],$p1_io);
$p2 = proc_open('cat /etc/passwd | grep root',[0=>$p1_io[1],1=>['pipe','w']],$p2_io);
echo stream_get_contents($p2_io[1]);
```
`ProcOpen` wrapper, shell call and pipe io
````php
<?php
## shell calling, explicitly use SHELL.
$p = new ProcOpen(['bash']);
$p->setInput('cat /etc/passwd | grep root');
$p->start();
echo $p->getOutput();
## pipe io , more safe and easy to maintenance.
$p1 = new ProcOpen(['cat','/etc/passwd']);
$p2 = new ProcOpen(['grep','root']);
$p1->start();
$p2->setInput($p1->stdout());
$p2->start();
echo $p2->getOutput();
````

## Test

phpunit 

```shell
composer install 
vendor/bin/phpunit 
vendor/bin/phpunit --filter ProcOpen
```
## examples 

See [Examples](https://github.com/takuya/php-proc_open-wrapper/tree/master/examples).

## safer process call

This package directly exec command, not using 'shell'.

proc_open executes command directly, without shell ( fork then exec ).

Using proc_open correctly, `directory traversal` vulnerability , `shell injection` vulnerability will not be occurred. Shell escaping will not be needed. this can reduce risks.

Don't escape string , use array. 

#### use cmd array. not escaping.
proc_open can accept cmd string, but cmd string may be danger(mal-escaping). exec cmd by array is more safe.

```php
<?php
$p = new ProcOpen( ['php','-v'] );
$p->start();
$p->wait();
//
$output = stream_get_contents($p->getFd(1));
```

you must check arguments.

To prevent directory traversal. you must check args in advance. cmd string cannot be checked, but array can check.
```php
<?php
$file_name = '../../../../../../../../etc/shadow';
$file_name = realpath('/my/app_root/'.basename($file_name);// false
proc_open(['cat',$file_name]...);
```

## pipe process

Make easy to pipe process by proc_open.

```php
<?php
//
// run `php -i | grep pcntl` by ProcOpen class 
//
$p1 = new ProcOpen( ['php','-i'] );
$p1->start();

$p2 = new ProcOpen(['grep','pcntl'])
$p2->setInput($p1->getFd(1));//pipe p1->stdout to p2->stdin
$p2->start();
// 
$p1->wait();
$p2->wait();
//
$output = stream_get_contents($p2->getFd(1));
```

pipe by pure php. It's very painful.
```php
<?php
//
// run `ls /etc | grep su` by proc_open() pipe
//
$p1_fd_res = [['pipe','r'],['pipe','w'],['pipe','w']];
$p1 = proc_open(['ls','/etc'],$p1_fd_res,$p1_pipes);
fclose($p1_pipes[0]);
$p2_fd_res = [$p1_pipes[1],['pipe','w'],['pipe','w']];
$p2 = proc_open(['grep','su'],$p2_fd_res,$p2_pipes);

while(proc_get_status($p1)["running"]){
usleep(100);
}
while(proc_get_status($p2)["running"]){
usleep(100);
}
//
$str = fread($p2_pipes[1],1024);
var_dump($str);
```

Use this ProcOpen class , reduce cost by naturally call proc_open.

## Run string as shell script. 
```php
<?php
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
```

## Run string as python code.

run python code in proc_open

```php
<?php
$proc = new ProcOpen(['python']);
$proc->setInput('
import os;
import sys
for i in range(5):
  print(i);
print("end")
print(sys.path)
');
$proc->start();
$proc->wait();
//blocking io
echo fread($proc->getFd(ProcOpen::STDOUT), 1024);
```

## Run php interactive shell `php -a `

```php
<?php
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

```
## Linux pipe max.

Linux pipe will be Stuck(blocked) if left unread.

`LINUX_PIPE_SIZE` is `1024*64`.
so if you try to write one more byte (`1024*64+1`bytes) to `stdout`, process will be blocked by OS.

```php
<?php
$proc = new ProcOpen( ['php'] );
$proc->setInput(<<<'EOS'
  <?php
  define('LINUX_PIPE_SIZE_MAX',1024*64+1);
  foreach(range(1,LINUX_PIPE_SIZE_MAX) as $i){
    echo 'a';
  }
  EOS);
$proc->start();
// this will be blocked.

```
To avoid BUFF stuck use blockingIO instead of wait.
```php
<?php
$popen = new ProcOpen(['php','-i'],null,$env);
$popen->start();
// instead of wait() use blockingIO.
return stream_get_contents($popen->stdout());
```

or, To avoid blocking, you can use tmp-io.

```php
<?php
$proc = new ProcOpen( ['php'] );
$proc->setInput(<<<'EOS'
  <?php
  define('LINUX_PIPE_SIZE_MAX',1024*64+1);
  foreach(range(1,LINUX_PIPE_SIZE_MAX) as $i){
    echo 'a';
  }
  EOS);
$proc->setStdout($tmp = fopen('php://temp','w'));
$proc->start();
// this will be successfully finished.
```

Or use `select syscall` and read the buffer.

```php
<?php
$proc = new ProcOpen( ['php'] );
$proc->setInput(<<<'EOS'
  <?php
  define('LINUX_PIPE_SIZE_MAX',1024*64+1);
  foreach(range(1,LINUX_PIPE_SIZE_MAX) as $i){
    echo 'a';
  }
  EOS);
$proc->start();
// use select not to be clogged.
$output = '';
$avail = stream_select( ...( $selected = [[$proc->getFd( 1 )], [], null, 0, 100] ) );
if ( $avail > 0 ) {
  $output .= fread( $proc->getFd( 1 ), 1 );
}
```

'php://temp' may be looks good, but that is not all good. It will be cast to TEMP FILE.

```php
$popen = new ProcOpen(['php','-i'],null,$env);
// This is not memory , proc_open cast IO to /tmp/phpXXXX .
$popen->setOutput($out=fopen('php://temp/maxmemory:'.(1024*1024*10)));
$popen->start();
$popen->wait();
// in case Ctrl-C  this will remain temp_file in /tmp 
echo $popen->getOutput();//
```
To use fopen wrapppers , flag the property before start.
```php
$popen = new ProcOpen(['php','-i'],null,$env);
$popen->enableBuffering();//FLAG
// after add Flag , memory can be used.
$popen->setOutput(fopen('php://memory','w+'));
$popen->start();
$popen->wait();
echo $popen->getOutput();// no blocking, no error.
```


