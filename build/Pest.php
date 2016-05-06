<?php

namespace Pest;

function ThreeLineWriter(Pest $pest, $tests, $config) {

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
        if ($recordsCount === $passedRecords && $test->getException() == null) {
            $passedTests++;
        }
    }
    if ($testsCount == $passedTests) {
        if (isset($config[\Pest\Pest::CONFIG_ONLY_FAILED])) {
            return;
        }
        echo $pest->getName();
    } else {
        echo $pest->getName();
    }
    echo PHP_EOL;
    printf("   Assertion status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $allPassedRecords, $allRecordsCount - $allPassedRecords, $allPassedRecords / $allRecordsCount * 100);
    printf("   Test status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $passedTests, $testsCount - $passedTests, $passedTests / $testsCount * 100);
    echo PHP_EOL;
}

class Record {

    private $status, $message, $stackInfo, $values, $skipped;

    public function __construct($status, $message, $stackInfo, array $values = array(), $skipped = 0) {
        $this->status = $status;
        $this->message = $message;
        $this->stackInfo = $stackInfo;
        $this->values = $values;
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
    function getValues() {
        return $this->values;
    }

    function setValues(array $values) {
        $this->values = $values;
    }


}

class Pest {

    private $tests = [];
    private $name = "";
    private $prepare;
    private $cleanUp;
    private $records = [];
    private $workingDir;
    private $options = self::OPTION_CHDIR;

    const OPTION_CHDIR = 1;
    const CONFIG_DEFAULT_WRITER_NAME = "DEFAULT_WRITER_NAME";
    const CONFIG_ONLY_FAILED = "ONLY_FAILED";

    private static $CONFIGURATION = array(
        self::CONFIG_DEFAULT_WRITER_NAME => "\Pest\DefaultWriter"
    );
    private static $EXIT_VALUE = 0;

    public function __construct($name, $options = null) {
        $this->name = $name;
        if ($options != null) {
            $this->options = $options;
        }
        if ($this->options & self::OPTION_CHDIR) {
            $this->workingDir = getCwd();
            $newCwd = dirname(debug_backtrace()[0]['file']);
            chdir($newCwd);
        }
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

    private function saveRecords(Test $test, $output, \Exception $exception = null) {
        $test->setRecords($this->records);
        $test->setOutput($output);
        $test->setException($exception);
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
        $writer($this, $this->tests, self::$CONFIGURATION);
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
            $exception = null;
            try {
                $code();
            } catch (\Exception $e) {
                $exception = $e;
            }
            $this->saveRecords($test, ob_get_clean(), $exception);
            if ($this->cleanUp) {
                $code = $this->cleanUp;
                $code();
            }
        }
        if ($writer == null) {
            $writer = self::$CONFIGURATION[self::CONFIG_DEFAULT_WRITER_NAME];
        }

        self::$EXIT_VALUE = $this->calculateExitValue();

        $this->write($writer);
    }

    private function calculateExitValue() {
        $passedTests = 0;
        foreach ($this->tests as $test) {
            $recordsCount = count($test->getRecords());
            $passedRecords = 0;
            foreach ($test->getRecords() as $record) {
                if ($record->getStatus()) {
                    $passedRecords++;
                } else {
                    if ($record->getSkipped() > 0) {
                        $recordsCount += $record->getSkipped();
                    }
                }
            }
            if ($recordsCount === $passedRecords) {
                $passedTests++;
            }
        }
        return 100 - (int) (($passedTests / count($this->tests)) * 100);
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
        $this->records[] = new Record($object == true, $message, $stackInfo, array($object));
        return $this;
    }

    public function assertFalse($object, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($object == false, $message, $stackInfo, array($object));
        return $this;
    }

    public function assertEmpty($object, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record(empty($object), $message, $stackInfo, array($object));
        return $this;
    }

    public function assertNotEmpty($object, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record(!empty($object), $message, $stackInfo, array($object));
        return $this;
    }

    public function assertEquals($a, $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($a == $b, $message, $stackInfo, array($a, $b));
        return $this;
    }

    public function assertSame($a, $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($a === $b, $message, $stackInfo, array($a, $b));
        return $this;
    }

    public function assertNotEquals($a, $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($a != $b, $message, $stackInfo, array($a, $b));
        return $this;
    }

    public function assertNotSame($a, $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record($a !== $b, $message, $stackInfo, array($a, $b));
        return $this;
    }

    public function assertSameValues(array $a, array $b, $message = "") {
        $stackInfo = $this->extractStackInfo();
        $this->records[] = new Record(count(array_diff($a, $b)) === 0 && count(array_diff($b, $a)) === 0, $message, $stackInfo, array($a, $b));
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

    public static function SETUP_EXIT_REWRITE() {
        register_shutdown_function(function() {
            if (error_get_last() == null) {
                exit(self::$EXIT_VALUE);
            }
        });
    }

    public static function SET_CONFIGURATION(array $configuration) {
        self::$CONFIGURATION = $configuration;
    }

    public function __destruct() {
        if ($this->options & self::OPTION_CHDIR) {
            chdir($this->workingDir);
        }
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

class Utils {

    /**
     * Stores all temporary files - created with Utils::TMP_FILE()
     * @var array 
     */
    private static $TMP_FILES = array();

    /**
     * a stack of last visited directories
     * @var array
     */
    private static $DIR_STACK = array();

    /**
     * Change Directory
     * @param string|null $dir the new working directory or null to go back to the old working directory
     * @return string last working directory
     * @throws \InvalidArgumentException if $dir is not null nor a directory
     */
    public static function CD($dir = null) {
        if (!is_null($dir)) {
            if (!is_dir($dir)) {
                throw new \InvalidArgumentException("expected directory");
            }
            self::$DIR_STACK[] = getcwd();
            chdir($dir);
            return end(self::$DIR_STACK);
        } else {
            if (count(self::$DIR_STACK) <= 0) {
                throw new \LogicException("empty directory stack");
            }
            $oldDir = getcwd();
            $dir = array_pop(self::$DIR_STACK);
            chdir($dir);
            return $oldDir;
        }
    }

    /**
     * Changes the working directory to the systems temporary directory and returns the "old" working directory
     * @return string the old working directory
     */
    public static function CD_TMP() {
        return self::CD(sys_get_temp_dir());
    }

    /**
     * Runs the $function in the given directory if the given directory is null 
     * it will use the temporary directory and then changes directory back to 
     * where it came from
     * @param callable $function
     * @param string $directory the directoy in which the code should run
     * @return mixed returns the output of $function
     */
    public static function RUN_IN(callable $function, $directory = null) {
        if (is_null($directory)) {
            self::CD_TMP();
        } else {
            self::CD($directory);
        }

        $return = $function();
        self::CD();
        return $return;
    }

    /**
     * Creates a temporary unique file in the systems temporary directory
     * @param string $prefix the prefix of the file name.
     * @return string path to the file
     */
    public static function TMP_FILE($prefix = "Pst") {
        return self::$TMP_FILES[] = tempnam(sys_get_temp_dir(), $prefix);
    }

    /**
     * Cleans up all temporary created files (Utils::TMP_FILE())
     */
    public static function RM_TMP_FILES() {
        foreach (self::$TMP_FILES as $key => $file) {
            unlink($file);
            unset(self::$TMP_FILES[$key]);
        }
    }

    /**
     * Remove a directory, recursively.
     * @param string $dir
     * @throws \InvalidArgumentException if $dir is not a directory or is not writable 
     */
    public static function RM_RF($dir) {
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException("expected name of a directory");
        }
        if (!is_writable($dir)) {
            throw new \InvalidArgumentException("directory is not writable");
        }
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getPathName());
            }
        }
        rmdir($dir);
    }

}

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

function LinuxWriter(Pest $pest, $tests, $config) {
    $colored = function ($text, $color) {
        return "\033[" . $color . "m" . $text . "\033[0m";
    };

    $dump = function($mixed) {
        switch (gettype($mixed)) {
            case 'string':
                return "string[" . strlen($mixed) . "](" . var_export($mixed, true) . ")";
            default :
                return gettype($mixed) . "(" . var_export($mixed, true) . ")";
        }
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

        if ($recordsCount === $passedRecords && ($exception = $test->getException()) == null) {
            $status = "[" . $colored("passed", 42) . "] ";
            $passedTests++;
            if (isset($config[\Pest\Pest::CONFIG_ONLY_FAILED])) {
                continue;
            }
        } else {
            $status = "[" . $colored("failed", 41) . "] ";
        }

        echo "   " . $status . $test->getName() . PHP_EOL . PHP_EOL;
        if (($exception = $test->getException()) != null) {
            $exception instanceof \Exception;
            echo "    Uncought exception:" . PHP_EOL.PHP_EOL;
            echo "        Type:    " . get_class($exception) . PHP_EOL;
            echo "        Message: " . $exception->getMessage() . PHP_EOL;
            echo "        Code:    " . $exception->getCode() . PHP_EOL;
            echo "        File:    " . $exception->getFile() . PHP_EOL;
            echo "        Line:    " . $exception->getLine() . PHP_EOL;
            echo "        Stacktrace:    " . PHP_EOL;
            echo "            ". str_replace("\n", "\n            ", $exception->getTraceAsString()) . PHP_EOL;

            continue;
        }

        foreach ($test->getRecords() as $record) {
            if ($record->getStatus()) {

                if (isset($config[\Pest\Pest::CONFIG_ONLY_FAILED])) {
                    continue;
                }
                $status = "      [" . $colored("passed", 42) . "] ";
            } else {
                $status = "      [" . $colored("failed", 41) . "] ";
            }
            echo $status . $record->getStackInfo()->getFunction() . (empty($record->getMessage()) ? "" : ": " . $record->getMessage()) . PHP_EOL . PHP_EOL;
            echo "         File: " . $record->getStackInfo()->getFile() . PHP_EOL;
            echo "         Line: " . ($record->getStackInfo()->getLine()) . PHP_EOL;
            echo "         " . $record->getStackInfo()->getCode() . PHP_EOL . PHP_EOL;
            if (!$record->getStatus()) {
                for ($i = 0; $i < count($record->getValues()); $i++) {
                    $value = $record->getValues()[$i];
                    echo "            #$i " . $dump($value) . PHP_EOL;
                }
            }
            echo PHP_EOL;
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

function DefaultWriter(Pest $pest, $tests, $config) {
    $dump = function($mixed) {
        switch (gettype($mixed)) {
            case 'string':
                return "string[" . strlen($mixed) . "](" . var_export($mixed, true) . ")";
            default :
                return gettype($mixed) . "(" . var_export($mixed, true) . ")";
        }
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

        if ($recordsCount === $passedRecords && ($exception = $test->getException()) == null) {
            $status = "[passed] ";
            $passedTests++;
            if (isset($config[\Pest\Pest::CONFIG_ONLY_FAILED])) {
                continue;
            }
        } else {
            $status = "[failed] ";
        }

        echo "   " . $status . $test->getName() . PHP_EOL . PHP_EOL;
        if (($exception = $test->getException()) != null) {
            $exception instanceof \Exception;
            echo "    Uncought exception:" . PHP_EOL.PHP_EOL;
            echo "        Type:    " . get_class($exception) . PHP_EOL;
            echo "        Message: " . $exception->getMessage() . PHP_EOL;
            echo "        Code:    " . $exception->getCode() . PHP_EOL;
            echo "        File:    " . $exception->getFile() . PHP_EOL;
            echo "        Line:    " . $exception->getLine() . PHP_EOL;
            echo "        Stacktrace:    " . PHP_EOL;
            echo "            ". str_replace("\n", "\n            ", $exception->getTraceAsString()) . PHP_EOL;

            continue;
        }


        foreach ($test->getRecords() as $record) {
            if ($record->getStatus()) {

                if (isset($config[\Pest\Pest::CONFIG_ONLY_FAILED])) {
                    continue;
                }
                $status = "      [passed] ";
            } else {
                $status = "      [failed] ";
            }
            echo $status . $record->getStackInfo()->getFunction() . (empty($record->getMessage()) ? "" : ": " . $record->getMessage()) . PHP_EOL . PHP_EOL;
            echo "         File: " . $record->getStackInfo()->getFile() . PHP_EOL;
            echo "         Line: " . ($record->getStackInfo()->getLine()) . PHP_EOL;
            echo "         " . $record->getStackInfo()->getCode() . PHP_EOL . PHP_EOL;
            if (!$record->getStatus()) {
                for ($i = 0; $i < count($record->getValues()); $i++) {
                    $value = $record->getValues()[$i];
                    echo "            #$i " . $dump($value) . PHP_EOL;
                }
            }
            echo PHP_EOL;
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

function ThreeLineLinuxWriter(Pest $pest, $tests, $config) {

    $colored = function ($text, $color) {
        return "\033[" . $color . "m" . $text . "\033[0m";
    };
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
        if ($recordsCount === $passedRecords && $test->getException() == null) {
            $passedTests++;
        }
    }
    if ($testsCount == $passedTests) {
        if (isset($config[\Pest\Pest::CONFIG_ONLY_FAILED])) {
            return;
        }
        echo $colored($pest->getName(), 42);
    } else {
        echo $colored($pest->getName(), 41);
    }
    echo PHP_EOL;
    printf("   Assertion status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $allPassedRecords, $allRecordsCount - $allPassedRecords, $allPassedRecords / $allRecordsCount * 100);
    printf("   Test status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $passedTests, $testsCount - $passedTests, $passedTests / $testsCount * 100);
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
        if (($exception = $test->getException()) != null) {
            $joTest->exception = new \stdClass;
            $joTest->exception->type = get_class($exception);
            $joTest->exception->message = $exception->getMessage();
            $joTest->exception->code = $exception->getCode();
            $joTest->exception->file = $exception->getFile();
            $joTest->exception->line = $exception->getLine();
            $joTest->exception->stacktrace = $exception->getTraceAsString();
        }
        $joTest->assertions = array();

        foreach ($test->getRecords() as $record) {
            $joRecord = new \stdClass();
            $joRecord->status = $record->getStatus();

            $joRecord->function = $record->getStackInfo()->getFunction();
            $joRecord->message = (empty($record->getMessage()) ? "" : ": " . $record->getMessage());
            $joRecord->file = $record->getStackInfo()->getFile();
            $joRecord->line = ($record->getStackInfo()->getLine());
            $joRecord->code = $record->getStackInfo()->getCode();
            $joRecord->values = $record->getValues();
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



if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
    $EXIT_VALUE = 0;
    if (count($argv) > 1 && is_dir($argv[1])) {
        $tests = $passedTests = 0;
        $dir = $argv[1];
        array_shift($argv);
        array_walk($argv, function(&$arg) {
            $arg = escapeshellarg($arg);
        });
        foreach (new \DirectoryIterator($dir) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            $argv[0] = escapeshellarg($dir . DIRECTORY_SEPARATOR . $fileInfo->getFilename());
            system($_SERVER['_'] . ' -d auto_prepend_file=' . __FILE__ . ' ' . implode(" ", $argv), $ex_val);
            $tests++;
            if ($ex_val == 0) {
                $passedTests++;
            }
        }
        if ($tests == 0) {
            $EXIT_VALUE = 101;
        } else {
            $EXIT_VALUE = 100 - (int) (($passedTests / $tests) * 100);
        }
    } else if (count($argv) > 1 && is_file($argv[1])) {
        array_shift($argv);
        array_walk($argv, function(&$arg) {
            $arg = escapeshellarg($arg);
        });
        system($_SERVER['_'] . ' -d auto_prepend_file=' . __FILE__ . ' ' . implode(" ", $argv) . ' ', $EXIT_VALUE);
    } else {
        echo <<<EOF
        Pest - Usage:
        
        pest [Test file | Dir with Test files ] [--<option>]* 
          
           Test file: Run this file as a php pest test file
           Dir with Test files: Run all files in this directory as a php pest test files
          
           
           --pest_writer        values:
                                    \Pest\LinuxWriter
                                    \Pest\DefaultWriter
                                    \Pest\JsonWriter
                                    \Pest\ThreeLineLinuxWriter
            
            
           --pest_noexit          Pest rewrites the exit code to the percentage of the failed tests. 
                                  Use this option if you are using own exit codes.
            
           --pest_only_failed     Show only failed Tests and failed assertions in the output. 
                                  This doesn't apply for the JsonWriter.
  
   
EOF;
    }
    exit($EXIT_VALUE);
} else {
    parseArgv($argv, $flags, $options, $arguments);
    $config = array();

    if (!isset($flags["pest_noexit"])) {
        \Pest\Pest::SETUP_EXIT_REWRITE();
    }
    if (isset($flags["pest_only_failed"])) {
        $config[\Pest\Pest::CONFIG_ONLY_FAILED] = true;
    }

    if (isset($options["pest_writer"])) {
        $writer = $options["pest_writer"];
    } else if (php_sapi_name() == 'cli') {
        if (stristr(PHP_OS, 'LINUX') || stristr(PHP_OS, 'DAR')) {
            $writer = "\Pest\LinuxWriter";
        } else if (stristr(PHP_OS, 'WIN') || true) {
            $writer = "\Pest\DefaultWriter";
        }
    } else {
        $writer = "\Pest\JsonWriter";
    }
    $config[\Pest\Pest::CONFIG_DEFAULT_WRITER_NAME] = $writer;






    \Pest\Pest::SET_CONFIGURATION($config);
}

function parseArgv(array $argv, &$flags, &$options, &$argumants) {
    foreach ($argv as $arg) {
        if (strlen($arg) > 1) {
            if ($arg{0} == "-" && $arg{1} != "-") {
                $strInfo = count_chars(substr($arg, 1), 3);
                foreach (str_split($strInfo) as $chr) {
                    $flags[$chr] = $chr;
                }
            } else if ($arg{0} == "-" && $arg{1} == "-") {
                if (($pos = strpos($arg, "=")) !== false) {
                    $pair = substr($arg, 2);
                    $key = substr($pair, 0, $pos - 2);
                    $value = substr($pair, $pos - 2 + 1);
                    $options[$key] = $value;
                } else {
                    $flags[substr($arg, 2)] = substr($arg, 2);
                }
            } else if (($pos = strpos($arg, "=")) !== false) {
                $pair = substr($arg, 2);
                $key = substr($pair, 0, $pos - 2);
                $value = substr($pair, $pos - 2 + 1);
                $options[$key] = $value;
            } else {
                $argumants[] = $arg;
            }
        }
    }
}

function toArgStr($arga) {
    $str = "";
    foreach ($arga['flags'] as $key => $value) {
        $str .= "-" . $value . " ";
    }
    foreach ($arga['options'] as $key => $value) {
        $str .= escapeshellarg("--" . $key . "=" . $value) . " ";
    }
    foreach ($arga['arguments'] as $arg) {
        $str .= escapeshellarg($arg) . " ";
    }

    return $str;
}
?>