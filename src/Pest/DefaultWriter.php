<?php

namespace Pest;

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
