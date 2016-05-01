<?php

namespace Pest;

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
        if ($recordsCount === $passedRecords) {
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
