<?php

namespace Takuya\ProcOpen\Traits;

trait SyntaxSugar {
  public function stderr () {
    return $this->getFd( self::STDERR );
  }
  public function stdout () {
    return $this->getFd( self::STDOUT );
  }
  public function stdin () {
    return $this->getFd( self::STDIN );
  }
  
}