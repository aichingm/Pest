<?php

namespace Pest;

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
