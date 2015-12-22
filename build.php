<?php

echo "Start building Pest" . PHP_EOL;
file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . "build" . DIRECTORY_SEPARATOR . "Pest.php", "");
$outFile = fopen(__DIR__ . DIRECTORY_SEPARATOR . "build" . DIRECTORY_SEPARATOR . "Pest.php", "r+");
fwrite($outFile, "<?php" . PHP_EOL);
fwrite($outFile, PHP_EOL);
fwrite($outFile, "namespace Pest;" . PHP_EOL);
fwrite($outFile, PHP_EOL);

foreach (new DirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . "Pest") as $fileInfo) {
    if ($fileInfo->isDot()) {
        continue;
    }
    echo $fileInfo->getFilename() . PHP_EOL;

    $file = file(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . "Pest" . DIRECTORY_SEPARATOR . $fileInfo->getFilename());


    while (strpos(trim($file[0]), "class") !== 0 && strpos(trim($file[0]), "function") !== 0) {
        array_shift($file);
    }
    foreach ($file as $line) {
        fwrite($outFile, $line);
    }
    fwrite($outFile, PHP_EOL);
}

$boilerplate = substr(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . "boilerplate.php"), 5);

fwrite($outFile, $boilerplate);
fwrite($outFile, "?>");
fclose($outFile);
$output = array();
exec($_SERVER["_"] . " -l build" . DIRECTORY_SEPARATOR . "Pest.php 2>&1 /dev/null ", $output, $return_var);
if($return_var != 0){
    echo PHP_EOL."Build faild. Output file has errors.".PHP_EOL;
    echo "    ".implode(PHP_EOL."    ", $output).PHP_EOL;
    exit(1);
}else{
    echo PHP_EOL."Build was successfull.".PHP_EOL;
    exit(0);
}
