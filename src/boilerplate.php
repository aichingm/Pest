<?php


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
            system($_SERVER['_'] . ' -d auto_prepend_file=' . __FILE__ . ' '. $fileInfo->getFilename() . ' --pest_writer "\Pest\ThreeLineLinuxWriter"', $ex_val);
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


