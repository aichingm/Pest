<?php

namespace Pest;

class Pest {

    private $tests = [];
    private $name = "";
    private $prepare;
    private $cleanUp;
    private $records = [];
    public static $DEFAULT_WRITER_NAME = "\Pest\DefaultWriter";

    public function __construct($name) {
        $this->name = $name;
    }

    function getName() {
        return $this->name;
    }

    function test($name, callable $testCode) {
        $this->tests[] = $test = new Test();
        $test->setName($name);
        $test->setCode($testCode);
    }

    public function prepare(callable $prepareCode) {
        $this->prepare = $prepareCode;
    }

    public function cleanUp(callable $cleanUpCode) {
        $this->cleanUp = $cleanUpCode;
    }

    private function cleanUpRecords() {
        $this->records = [];
    }

    private function saveRecords(Test $test, $output) {
        $test->setRecords($this->records);
        $test->setOutput($output);
    }

    private function extractStackInfo($depth = 1) {
        $bt = debug_backtrace()[$depth];
        return new StackInfo($bt["function"], $bt["line"], $bt["file"], $bt["args"]);
    }

    private function extractExceptionInfo(\Exception $exception, $depth = 5) {
        $exDepth = count($exception->getTrace());
        if ($exDepth >= $depth) {
            $bt = $exception->getTrace()[$exDepth - $depth];
            if ($bt["function"] == "{closure}") {
                $line = explode("\n", $exception->getTraceAsString())[$exDepth - $depth + 1];
                $lineParts = explode(" ", $line);
                return new StackInfo("Object(closure)", substr($lineParts[1], strrpos($lineParts[1], "(") + 1, -2), substr($lineParts[1], 0, strrpos($lineParts[1], "(")), "", substr($line, strpos($line, ": ") + 2));
            } else {
                return new StackInfo($bt["function"], $bt["line"], $bt["file"], $bt["args"]);
            }
        } else {
            return $this->extractExceptionInfo($exception, $depth - 1);
        }
    }

    public function write(callable $writer) {
        $writer($this, $this->tests);
    }

    public function run(callable $writer = null) {

        foreach ($this->tests as $test) {
            if ($this->prepare) {
                $code = $this->prepare;
                $code();
            }
            $this->cleanUpRecords();
            $code = $test->getCode();
            ob_start();
            $code();
            $this->saveRecords($test, ob_get_clean());
            if ($this->cleanUp) {
                $code = $this->cleanUp;
                $code();
            }
        }
        if ($writer == null) {
            $writer = self::$DEFAULT_WRITER_NAME;
        }
        $this->write($writer);
    }

    public function last($skip = 0) {
        if (count($this->records) > 0) {
            if (end($this->records)->getStatus()) {
                return true;
            } else {
                if ($skip > 0) {
                    end($this->records)->setSkipped($skip);
                }
                return false;
            }
        }
        return false;
    }

    public function assertTrue($object, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($object == true, $message, $stackInfo);
        return $this;
    }

    public function assertFalse($object, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($object == false, $message, $stackInfo);
        return $this;
    }

    public function assertEmpty($object, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record(empty($object), $message, $stackInfo);
        return $this;
    }

    public function assertNotEmpty($object, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record(!empty($object), $message, $stackInfo);
        return $this;
    }

    public function assertEquals($a, $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($a == $b, $message, $stackInfo);
        return $this;
    }

    public function assertSame($a, $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($a === $b, $message, $stackInfo);
        return $this;
    }

    public function assertNotEquals($a, $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($a != $b, $message, $stackInfo);
        return $this;
    }

    public function assertNotSame($a, $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($a !== $b, $message, $stackInfo);
        return $this;
    }

    public function assertSameValues(array $a, array $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record(count(array_diff($a, $b)) === 0 && count(array_diff($b, $a)) === 0, $message, $stackInfo);
        return $this;
    }

    public function expectAnyException(callable $condition, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $status = false;
        try {
            $condition();
        } catch (\Exception $exc) {
            $status = true;
            $newStackInfo = $this->extractExceptionInfo($exc);
            $newStackInfo->setFunction($stackInfo->getFunction());
            $stackInfo = $newStackInfo;
        }
        $this->records[] = new Record($status, $message, $stackInfo);
        return $this;
    }

    public function expectException(callable $condition, $type, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $status = false;
        try {
            $condition();
        } catch (\Exception $exc) {
            $status = $exc instanceof $type;
            $newStackInfo = $this->extractExceptionInfo($exc);
            $newStackInfo->setFunction($stackInfo->getFunction());
            $stackInfo = $newStackInfo;
        }
        $this->records[] = new Record($status, $message, $stackInfo);
        return $this;
    }

    public function noException(callable $condition, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $status = true;
        try {
            $condition();
        } catch (\Exception $exc) {
            $status = false;
            $stackInfo = $this->extractExceptionInfo($exc);
        }
        $this->records[] = new Record($status, $message, $stackInfo);
        return $this;
    }

}
