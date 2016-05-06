<?php

namespace Pest;

class Test {

    private $code, $name, $records = [], $output, $exception;

    public function getCode() {
        return $this->code;
    }

    public function getName() {
        return $this->name;
    }

    public function getRecords() {
        return $this->records;
    }

    public function getOutput() {
        return $this->output;
    }

    public function getException() {
        return $this->exception;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setRecords($records) {
        $this->records = $records;
    }

    public function setOutput($output) {
        $this->output = $output;
    }

    public function setException(\Exception $exception = null) {
        $this->exception = $exception;
    }

}
