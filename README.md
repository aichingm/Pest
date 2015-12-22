#Pest#
Pest is a micro unit testing framework for php applications. 

As the name says testing isn't always a joy so I developed Pest (PHP-test) to make testing as simple and fast as possible. 
No need to learn how to write tests or use a clunky testing framework if you know php already.

##Requirements##
No requirements - except php 5.4+

##Instalation##
Pest builds in to a single file located under `build/Pest.php`. This file is the only file you need to run Pest compatible unit tests. 
You can download it from github.com or clone the repository and copy it from the working tree.


##Examples##

    <?php
    
    $t = new Pest\Pest("Example Test Suite");
    
    $t->test("Test Your First Method", function() use ($t) {
        $t->assertEquals(5, 2+3, "2 plus 3 is 5");
    });

_Note_ You don't need to require a specific version of Pest or any other Pest like implementation.

##Run Tests##
Run a test file with:
    
    php pathTo/Pest.php myTestsFile.php

Run all test files in a directory;
    
    php pathTo/Pest.php myTestsDirectory

Run all tests in the current directory where the name of the file starts with a given prefix:

    php pathTo/Pest.php TestPrefix_

To Run tests without starting the Pest program (Pest will only try to set the writer), run:

    php -d auto_prepend_file=pathTo/Pest.php myTestsFile.php

_Note_ This is useful if the test file which you want to run needs for example the $_SERVER['self'] variable set to your file and not `pathTo/Pest.php` 

_Note_ This is also useful if you want to quickly switch between Pest implementations.

_Note_ Pest will __not__ change the exit code if you use this method. The command line call will exit with whatever you test script exits!

##Documentation##

###Getting Started###
Creating a new test suite object:

    $test = new \Pest\Pest("MyTests");

Creating a new test:
The first parameter is the name of the test, the second is the code which should be tested encapsulated in an anonymous function without any parameters. 
 

    $test->test("Tests for function XY", function(){

    });

Creating a new test and test an assertion:
To do this we need to inject the test suit object into the scope of the anonymous function with `use ($test)`. 
We did it! Thats all you need to know to get started.

    $test->test("Tests for function XY", function() use ($test){
        $test->assertEquals(5, 2 + 3);
    });

###Assertion Methods###


_Note_ The $message parameter is not necessary!

**assertTrue($object, $message = "")** tests if something equals true (==).
   
    $test->assertTrue(true, "true equals true"); 

 **assertFalse($object, $message = "")** tests if something equals false (==).
   
    $test->assertTrue(false, "false equals false"); 
 
 **assertEmpty($object, $message = "")** tests if something is empty (empty()).
   
    $test->assertEmpty(null, "null is empty");   

**assertEquals($a, $b, $message = "")** tests if $a and $b are equal (==).
   
    $test->assertEqulas('1', 1, "'1' equlas 1");   

**assertSame($a, $b, $message = "")** tests if $a and $b are the same (===).
   
    $test->assertEqulas(1, 1, "1 is the same as 1");   

**assertNotEquals($a, $b, $message = "")** tests if $a and $b are not equal (!=).
   
    $test->assertEqulas(0, 1, "0 not equlas 1");   

**assertSame($a, $b, $message = "")** tests if $a and $b are not the same (!==).
   
    $test->assertEqulas(1, '1', "1 is not the same as '1'");   

**assertSameValues(array $a, array $b, $message = "")** tests if the array $a and the array $b have the exact same values.
   
    $test->assertEqulas([3, 2, 1], [1, 2, 3], "They are the same");


###Exception Methods###

_Note_ The $message parameter is not necessary!

**expectAnyException(callable $condition, $message = "")** tests if an exception occurs, fails if no exception gets thrown.

    expectAnyException(function(){throw new \Exception();}, "This will pass the test an exception was expected")

**expectException(callable $condition, $type, $message = "")** tests if an exception of a certain type occurs, fails if no exception gets thrown or the thrown exception is of the wrong type.

    expectException(function(){throw new \ErrorException();}, "\ErrorException", "This will pass the test an "ErrorException" was expected")

**noException(callable $condition, $message = "")** tests if no exception occurs, fails if an exception gets thrown.

    noException(function(){try{throw new \ErrorException();}catch($e){}}, "This will pass the test. All exceptions are handled inside the function")

##Writer##
A writer is a function which formats the test results and displays it. 
You can switch between writers by appending `--pest_writer "\Pest\JsonWriter"` to your run command. 
`\Pest\JsonWriter` is the class name of the writer you want to use. If you want to use your own writer make sure that you preload (require) it somewhere. 
Currently there are three writers implemented: `DefaultWriter`, `LinuxWriter` and `JsonWriter`

1. DefaultWriter writes the results of the tests to the standard output.
1. LinuxWriter does the same as the `DefaultWriter` but colores the output (This may only work on unix systems).
1. JsonWriter writes the results of the tests in `json` format to the standard output.
1. ThreeLineLinuxWriter writes the results of the tests in a very short and colored format (only three lines) to the standard output (This may only work on unix systems).

##Integrating Pest##
1. JsonWriter
1. Exit Codes

###JsonWriter###

Generally this is the best way to get computable data out of Pest. The Json format is widely used and has implementations in various programming languages.

###Exit Codes###

Pest, if not used via `-d autp_prepend_file=abc/Pest.php`, exits with the percentage of the test which __failed__! 
`0` means all test passed, `100` means all tests failed.

_Note_ All exit codes above `100` are reserved for errors and future functions.



##Building Pest##
To keep Pest a single file program it is needed to run the `build.php` script in the root of the repository. It will take all files in `src/Pest` and bundle them in to one file stored in build/Pest.php.
To run the build script simply run `php build.php` from the command line. 
_Note_ Please run the the frameworks tests to make sure that every thing is cool. 

##Running the Pest Tests##
To test the framework run `php -d auto_prepend_file=build/Pest.php tests/tests.php`.

##License##

    The MIT License (MIT)
    
    Copyright (c) 2015 Mario Aichinger <aichingm@gmail.com>
    
    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.
    
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.


