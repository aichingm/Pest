<?php

namespace Pest;

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

function ThreeLineLinuxWriter(Pest $pest, $tests) {

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
        if ($recordsCount === $passedRecords) {
            $passedTests++;
        }
    }
    if($testsCount == $passedTests){
        echo $colored($pest->getName(), 42);
    }else{
        echo $colored($pest->getName(), 41);
    }
    echo PHP_EOL;
    printf("   Assertion status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $allPassedRecords, $allRecordsCount - $allPassedRecords, $allPassedRecords / $allRecordsCount * 100);
    printf("   Test status: [passed: %d, failed: %d], success rate: %01.2f%%\n", $passedTests, $testsCount - $passedTests, $passedTests / $testsCount * 100);
    echo PHP_EOL;

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
            if (!$record->getStatus()) {
                for ($i = 0; $i < count($record->getValues()); $i++) {
                    $value = $record->getValues()[$i];
                    echo "            #$i " . gettype($value) . "(" . var_export($value, true) . ")" . PHP_EOL;
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
    public static function CD($dir = null){
        if(!is_null($dir)){
            if(!is_dir($dir)){
                throw new \InvalidArgumentException("expected directory");
            }
            self::$DIR_STACK[] = getcwd();
            chdir($dir);
            return end(self::$DIR_STACK);
        }else{
            if(count(self::$DIR_STACK) <= 0){
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
            if (!$record->getStatus()) {
                for ($i = 0; $i < count($record->getValues()); $i++) {
                    $value = $record->getValues()[$i];
                    echo "            #$i " . gettype($value) . "(" . var_export($value, true) . ")" . PHP_EOL;
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
    public static $DEFAULT_WRITER_NAME = "\Pest\DefaultWriter";
    private static $EXIT_VALUE = 0;

    public function __construct($name, $options = null) {
        $this->name = $name;
        if($options != null){
            $this->options = $options;
        }
        if($this->options & self::OPTION_CHDIR){
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
        
        self::$EXIT_VALUE = $this->calculateExitValue();
        
        $this->write($writer);
    }
    private function calculateExitValue(){
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

    public function __destruct(){
        if($this->options & self::OPTION_CHDIR){
            chdir($this->workingDir);
        }
    }

}




 if (in_array("--pest_writer", $argv)) {
      \Pest\Pest::$DEFAULT_WRITER_NAME = $argv[array_search("--pest_writer", $argv) + 1];
 }


if(realpath($_SERVER['PHP_SELF']) == __FILE__){
    $EXIT_VALUE = 0;
    if(is_dir($argv[1])){
        $tests = $passedTests = 0;
        foreach (new \DirectoryIterator($argv[1]) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }
            system($_SERVER['_'] . ' -d auto_prepend_file=' . __FILE__ . ' '. $argv[1] . DIRECTORY_SEPARATOR . $fileInfo->getFilename() . ' --pest_writer "\Pest\ThreeLineLinuxWriter"', $ex_val);
            $tests++;
            if($ex_val == 0){
                $passedTests++;
            }
        }
        $EXIT_VALUE = 100 - (int)(($passedTests / $tests) * 100);
    }else if(is_file($argv[1])){
        system($_SERVER['_'] . ' -d auto_prepend_file=' . __FILE__ . ' '. $argv[1] . ' --pest_writer "' . \Pest\Pest::$DEFAULT_WRITER_NAME . '"', $EXIT_VALUE);
    }else if(isset($argv[1]) && $argv[1]{0} != '-'){
        $tests = $passedTests = 0;
        foreach (new \DirectoryIterator(getcwd()) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }
     
            if(strpos($fileInfo->getFilename(), $argv[1]) === 0){
                system($_SERVER['_'] . ' -d auto_prepend_file=' . __FILE__ . ' '. $fileInfo->getFilename() . ' --pest_writer "\Pest\ThreeLineLinuxWriter"', $ex_val);
                $tests++;
                if($ex_val == 0){
                    $passedTests++;
                }
            }
        }
        $EXIT_VALUE = 100 - (int)(($passedTests / $tests) * 100);
    }
    exit($EXIT_VALUE);
}else if (!in_array("--pest_noexit", $argv)) {
     \Pest\Pest::SETUP_EXIT_REWRITE(); 
}


?>