<?php

namespace Pest;

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
