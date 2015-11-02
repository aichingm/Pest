<?php

namespace Pest;

class Record {

    private $status, $message, $stackInfo, $skipped;

    public function __construct($status, $message, $stackInfo, $skipped = 0) {
        $this->status = $status;
        $this->message = $message;
        $this->stackInfo = $stackInfo;
        $this->skipped = $skipped;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getStackInfo() {
        return $this->stackInfo;
    }

    public function getSkipped() {
        return $this->skipped;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function setStackInfo($stackInfo) {
        $this->stackInfo = $stackInfo;
    }

    public function setSkipped($skipped) {
        $this->skipped = $skipped;
    }

}
