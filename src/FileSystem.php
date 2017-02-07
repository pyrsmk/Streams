<?php

namespace Streams;

use GuzzleHttp\Promise\Promise;
use DirectoryIterator;

/*
    Base FileSystem stream class
*/
abstract class FileSystem extends AbstractStream {
    
    /*
        Scan a directory
        
        Parameters
            string $directory
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _scanDirectory($directory) {
        // Prepare
        $files = [];
        $promise = new Promise();
        // Verify directory
        if(!is_readable($directory)) {
            throw new Exception("'$directory' is not readable");
        }
        if(!is_dir($directory)) {
            throw new Exception("'$directory' is not a directory");
        }
        // Browse directory
        foreach(new DirectoryIterator($directory) as $file) {
            // Reject dots
            if($file->isDot()) {
                continue;
            }
            // Verify file
            if(!$file->isReadable()) {
                $filename = $file->getFilename();
                throw new Exception("'$filename' is not readable");
            }
            // Save file
            if($file->isFile()) {
                $files[] = clone $file;
            }
        }
        // Resolve promise
        $promise->resolve($files);
        return $promise;
    }
    
}