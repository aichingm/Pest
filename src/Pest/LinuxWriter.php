<?php

namespace Pest;

function LinuxWriter(Pest $pest, $tests) {

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
