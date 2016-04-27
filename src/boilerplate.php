<?php

if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
    $EXIT_VALUE = 0;
    if (is_dir($argv[1])) {
        $tests = $passedTests = 0;
        foreach (new \DirectoryIterator($argv[1]) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }
            array_shift($argv);
            array_walk($argv, function(&$arg) {
                $arg = escapeshellarg($arg);
            });
            $argv[0] = $argv[0] . DIRECTORY_SEPARATOR . $fileInfo->getFilename();
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
    } else if (is_file($argv[1])) {
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
          
           
           --pest_writer    values:
                                \Pest\LinuxWriter
                                \Pest\DefaultWriter
                                \Pest\JsonWriter
                                \Pest\ThreeLineLinuxWriter
          
          
          --pest_noexit     Pest rewrites the exit code to the percentage of the failed tests. 
                            Use this option if you are using own exit codes.
  
   
EOF;
    }
    exit($EXIT_VALUE);
} else {
    parseArgv($argv, $flags, $options, $arguments);
    $config = array();
    
    if (!isset($flags["pest_noexit"])) {
        \Pest\Pest::SETUP_EXIT_REWRITE();
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
    
    
    
    
    
    
    \Pest\Pest::SET_CONFIGURATION($config)   ;
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
