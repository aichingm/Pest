<?php

$t = new Pest\Pest("Pest Test");

$t->test("AssertTrue", function() use ($t) {
    $t->assertTrue($t instanceof Pest\Pest)
            ->assertTrue(true)
            ->assertTrue(is_int(1), "1 is an int");
});
$t->test("AssertFalse", function() use ($t) {
    $t->assertFalse(false)
            ->assertFalse(!is_int(1));
});
$t->test("AssertEqual", function() use ($t) {
    $t->assertEquals("1", "1")
            ->assertEquals(null, false)
            ->assertEquals("1", 1)
            ->assertEquals(null, 0)
            ->assertEquals("", 0)
            ->assertEquals(null, [])
            ->assertEquals(false, [])
            ->assertEquals([], [])
            ->assertEquals(new stdClass, new stdClass);
});
$t->test("AssertNotEqual", function() use ($t) {
    $t->assertNotEquals("1", false)
            ->assertNotEquals(true, false)
            ->assertNotEquals("1", 2)
            ->assertNotEquals([], new stdClass)
            ->assertNotEquals(null, new stdClass);
});
$t->test("AssertSame", function() use ($t) {
    $a = $b = new stdClass;
    $t->assertSame($a, $b)
            ->assertSame(1, 1)
            ->assertSame(null, null)
            ->assertSame([], []);
    $c = [];
    $d = [];
    $t->assertSame($c, $d)
            ->assertSame(0.3, 0.3)
            ->assertSame(10, 0xA)
            ->assertSame(0xa, 0xA)
            ->assertSame(0xa, 0b1010);
});
$t->test("AssertNotSame", function() use ($t) {
    $t->assertNotSame(new stdClass, new stdClass)
            ->assertNotSame("1", 1)
            ->assertNotSame(true, 1)
            ->assertNotSame(0.1 + 0.2, 0.3);
});
$t->test("ExpectAnyException", function() use ($t) {
    $t->expectAnyException(function() {
                throw new ErrorException;
            })
            ->expectAnyException(function() {
                throw new InvalidArgumentException;
            });
});
$t->test("ExpectException", function() use ($t) {
    $t->expectException(function() {
                throw new ErrorException;
            }, "\ErrorException")
            ->expectException(function() {
                throw new InvalidArgumentException;
            }, "\InvalidArgumentException");
});
$t->test("NoException", function() use ($t) {
    $t->noException(function() {
                
            })
            ->noException(function() {
                try {
                    throw new InvalidArgumentException;
                } catch (Exception $exc) {
                    
                }
            });
});

$dataBox = new stdClass();
$t->prepare(function() use ($dataBox) {
    $dataBox->object = new stdClass();
});
$t->test("Test Prepare 1", function() use ($t, $dataBox) {
    $t->assertTrue($dataBox->object instanceof \stdClass);
    $dataBox->object->name = "Some Object";
    $t->assertEquals($dataBox->object->name, "Some Object");
});
$t->test("Test Prepare 2", function() use ($t, $dataBox) {
    $t->assertNotEquals($dataBox->object->name, "Some Object");
    $t->assertEmpty($dataBox->object->name);
});

$t->cleanUp(function() use ($dataBox) {
    $dataBox->cleanUpObject = new stdClass();
});
$t->test("Test CleanUp 1", function() use ($t, $dataBox) {
    $t->assertTrue($dataBox->object instanceof \stdClass);
    $dataBox->cleanUpObject->name = "Some Object";
    $t->assertEquals($dataBox->cleanUpObject->name, "Some Object");
});
$t->test("Test CleanUp 2", function() use ($t, $dataBox) {
    $t->assertNotEquals($dataBox->cleanUpObject->name, "Some Object");
    $t->assertEmpty($dataBox->cleanUpObject->name);
});


$t->run();

