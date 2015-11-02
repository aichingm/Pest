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

class Test {

    private $code, $name, $records = [], $output;

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

}

function LinuxWriter(Pest $pest, $tests) {

    $colored = function ($text, $color) {
        return "\033[" . $color . "m" . $text . "\033[0m";
    };

    echo str_pad("", 80, '#') . PHP_EOL;
    echo PHP_EOL;
    echo "   " . $pest->getName() . PHP_EOL;
    echo PHP_EOL;
    echo str_pad("", 80, '#') . PHP_EOL;
    $testsCount = count($tests);
    $passedTests = 0;
    $allRecordsCount = 0;
    $allPassedRecords = 0;
    foreach ($tests as $test) {
        $allRecordsCount += $recordsCount = count($test->getRecords());
        $passedRecords = 0;
        foreach ($test->getRecords() as $record) {
            if ($record->getStatus()) {
                $passedRecords++;
                $allPassedRecords++;
            } else {
                if ($record->getSkipped() > 0) {
                    $allRecordsCount += $record->getSkipped();
                    $recordsCount += $record->getSkipped();
                }
            }
        }
        if ($recordsCount === $passedRecords) {
            $status = "[" . $colored("passed", 42) . "] ";
            $passedTests++;
        } else {
            $status = "[" . $colored("failed", 41) . "] ";
        }

        echo "   " . $status . $test->getName() . PHP_EOL . PHP_EOL;

        foreach ($test->getRecords() as $record) {
            if ($record->getStatus()) {
                $status = "      [" . $colored("passed", 42) . "] ";
            } else {
                $status = "      [" . $colored("failed", 41) . "] ";
            }
            echo $status . $record->getStackInfo()->getFunction() . (empty($record->getMessage()) ? "" : ": " . $record->getMessage()) . PHP_EOL . PHP_EOL;
            echo "         File: " . $record->getStackInfo()->getFile() . PHP_EOL;
            echo "         Line: " . ($record->getStackInfo()->getLine()) . PHP_EOL;
            echo "         " . $record->getStackInfo()->getCode() . PHP_EOL . PHP_EOL;
            if ($record->getSkipped() > 0) {
                echo "         " . $colored("SKIPPED", 43) . " " . $record->getSkipped() . " assertions because of this assertion" . PHP_EOL . PHP_EOL;
            }
        }

        if (!empty($test->getOutput())) {
            echo "   Test output:" . PHP_EOL;
            foreach (explode(PHP_EOL, $test->getOutput()) as $line) {
                echo "         " . $line . PHP_EOL;
            }
        }


        printf("   Assertion status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $passedRecords, $recordsCount - $passedRecords, $passedRecords / $recordsCount * 100);
        echo "   " . str_pad("", 77, '-') . PHP_EOL;
    }

    echo PHP_EOL;
    echo str_pad("", 80, '#') . PHP_EOL;
    printf("\n   Assertion status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $allPassedRecords, $allRecordsCount - $allPassedRecords, $allPassedRecords / $allRecordsCount * 100);
    printf("\n   Test status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $passedTests, $testsCount - $passedTests, $passedTests / $testsCount * 100);
    echo PHP_EOL;
}

function DefaultWriter(Pest $pest, $tests) {
    echo str_pad("", 80, '#') . PHP_EOL;
    echo PHP_EOL;
    echo "   " . $pest->getName() . PHP_EOL;
    echo PHP_EOL;
    echo str_pad("", 80, '#') . PHP_EOL;
    $testsCount = count($tests);
    $passedTests = 0;
    $allRecordsCount = 0;
    $allPassedRecords = 0;
    foreach ($tests as $test) {
        $allRecordsCount += $recordsCount = count($test->getRecords());
        $passedRecords = 0;
        foreach ($test->getRecords() as $record) {
            if ($record->getStatus()) {
                $passedRecords++;
                $allPassedRecords++;
            } else {
                if ($record->getSkipped() > 0) {
                    $allRecordsCount += $record->getSkipped();
                    $recordsCount += $record->getSkipped();
                }
            }
        }
        if ($recordsCount === $passedRecords) {
            $status = "[passed] ";
            $passedTests++;
        } else {
            $status = "[failed] ";
        }

        echo "   " . $status . $test->getName() . PHP_EOL . PHP_EOL;

        foreach ($test->getRecords() as $record) {
            if ($record->getStatus()) {
                $status = "      [passed] ";
            } else {
                $status = "      [failed] ";
            }
            echo $status . $record->getStackInfo()->getFunction() . (empty($record->getMessage()) ? "" : ": " . $record->getMessage()) . PHP_EOL . PHP_EOL;
            echo "         File: " . $record->getStackInfo()->getFile() . PHP_EOL;
            echo "         Line: " . ($record->getStackInfo()->getLine()) . PHP_EOL;
            echo "         " . $record->getStackInfo()->getCode() . PHP_EOL . PHP_EOL;
            if ($record->getSkipped() > 0) {
                echo "         SKIPPED " . $record->getSkipped() . " assertions because of this assertion" . PHP_EOL . PHP_EOL;
            }
        }

        if (!empty($test->getOutput())) {
            echo "   Test output:" . PHP_EOL;
            foreach (explode(PHP_EOL, $test->getOutput()) as $line) {
                echo "         " . $line . PHP_EOL;
            }
        }


        printf("   Assertion status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $passedRecords, $recordsCount - $passedRecords, $passedRecords / $recordsCount * 100);
        echo "   " . str_pad("", 77, '-') . PHP_EOL;
    }

    echo PHP_EOL;
    echo str_pad("", 80, '#') . PHP_EOL;
    printf("\n   Assertion status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $allPassedRecords, $allRecordsCount - $allPassedRecords, $allPassedRecords / $allRecordsCount * 100);
    printf("\n   Test status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $passedTests, $testsCount - $passedTests, $passedTests / $testsCount * 100);
    echo PHP_EOL;
}

function JsonWriter(Pest $pest, $tests) {

    $jo = new \stdClass();
    $jo->unitName = $pest->getName();
    $jo->testsCount = count($tests);
    $jo->tests = array();

    $passedTests = 0;
    $allRecordsCount = 0;
    $allPassedRecords = 0;
    foreach ($tests as $test) {
        $joTest = new \stdClass();
        $allRecordsCount += $recordsCount = count($test->getRecords());
        $passedRecords = 0;
        foreach ($test->getRecords() as $record) {
            if ($record->getStatus()) {
                $passedRecords++;
                $allPassedRecords++;
            } else {
                if ($record->getSkipped() > 0) {
                    $allRecordsCount += $record->getSkipped();
                    $recordsCount += $record->getSkipped();
                }
            }
        }
        if ($recordsCount === $passedRecords) {
            $passedTests++;
        }
        $joTest->name = $test->getName();
        $joTest->assertions = array();

        foreach ($test->getRecords() as $record) {
            $joRecord = new \stdClass();
            $joRecord->status = $record->getStatus();

            $joRecord->function = $record->getStackInfo()->getFunction();
            $joRecord->message = (empty($record->getMessage()) ? "" : ": " . $record->getMessage());
            $joRecord->file = $record->getStackInfo()->getFile();
            $joRecord->line = ($record->getStackInfo()->getLine());
            $joRecord->code = $record->getStackInfo()->getCode();
            $joRecord->skipped = $record->getSkipped();
            $joTest->assertions[] = $joRecord;
        }

        $joTest->output = $test->getOutput();
        $joTest->assertionStatus = array("passed" => $passedRecords, "failed" => $recordsCount - $passedRecords);
        $jo->tests[] = $joTest;
    }

    $jo->assertionStatus = array("passed" => $allPassedRecords, "failed" => $allRecordsCount - $allPassedRecords);
    $jo->testsStatus = array("passed" => $passedTests, "failed" => $jo->testsCount - $passedTests);
    echo json_encode($jo, JSON_PRETTY_PRINT);
}

if (in_array("--pest_writer", $argv)) {
    \Pest\Pest::$DEFAULT_WRITER_NAME = $argv[array_search("--pest_writer", $argv) + 1];
}
?>