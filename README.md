## Run Process.


Run process and run string by interpreter(ex.python,bash). and keep process long time task running.

`proc_open` is distinctive style. so I make `proc_open` EASY and friendly.


## Installing


```

```
## Test

phpunit 

```shell
composer install 
vendor/bin/phpunit 
vendor/bin/phpunit --filter ProcOpen
```


## safer process call

This package directory exec command, not using 'shell'.

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

Linux pipe will be blocked if left unread.

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

To avoid blocking, you should use tmp-io.

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

