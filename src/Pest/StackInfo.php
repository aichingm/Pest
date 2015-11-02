<?php

namespace Pest;

class StackInfo {

    private $function, $line, $file, $args, $code = null;

    function __construct($function, $line, $file, $args, $code = null) {
        $this->function = $function;
        $this->line = $line;
        $this->file = $file;
        $this->args = $args;
        $this->code = $code;
    }

    public function getFunction() {
        return $this->function;
    }

    public function getLine() {
        return $this->line;
    }

    public function getCode() {
        if ($this->code == null) {
            return trim(file($this->getFile())[$this->getLine() - 1]);
        }
        return $this->code;
    }

    public function getFile() {
        return $this->file;
    }

    public function getArgs() {
        return $this->args;
    }

    public function setFunction($function) {
        $this->function = $function;
    }

    public function setLine($line) {
        $this->line = $line;
    }

    public function setFile($file) {
        $this->file = $file;
    }

    public function setArgs($args) {
        $this->args = $args;
    }

}
