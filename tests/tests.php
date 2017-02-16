<?php

################################################## Init

require_once('../vendor/autoload.php');
require_once('vendor/autoload.php');

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
set_time_limit(0);

################################################## Verify data consistency

function verifyConsistency(array $elements) {
    foreach($elements as $id => $element) {
        if(!is_string($id) || strlen($id) != 32) {
            throw new Exception("'$id' id is invalid");
        }
        if(!isset($element['type'])) {
            throw new Exception("'type' field is required");
        }
        switch($element['type']) {
            case 'text':
                $schema = [
                    'type' => 'string',
                    'date' => 'integer',
                    'author' => 'string',
                    'avatar' => 'string',
                    'title' => 'string',
                    'description' => 'string',
                    'permalink' => 'string'
                ];
                break;
            case 'image':
                $schema = [
                    'type' => 'string',
                    'date' => 'integer',
                    'author' => 'string',
                    'avatar' => 'string',
                    'title' => 'string',
                    'description' => 'string',
                    'permalink' => 'string',
                    'source' => 'string',
                    'width' => 'integer',
                    'height' => 'integer',
                    'mimetype' => 'string'
                ];
                break;
            case 'video':
                $schema = [
                    'type' => 'string',
                    'date' => 'integer',
                    'author' => 'string',
                    'avatar' => 'string',
                    'title' => 'string',
                    'description' => 'string',
                    'permalink' => 'string',
                    'source' => 'string',
                    'width' => 'integer',
                    'height' => 'integer',
                    'mimetype' => 'string',
                    'preview' => 'string'
                ];
                break;
            case 'embed':
                $schema = [
                    'type' => 'string',
                    'date' => 'integer',
                    'author' => 'string',
                    'avatar' => 'string',
                    'title' => 'string',
                    'description' => 'string',
                    'permalink' => 'string',
                    'html' => 'string',
                    'width' => 'integer',
                    'height' => 'integer',
                    'preview' => 'string'
                ];
                break;
            default:
                throw new Exception("Unsupported '$type' element type");
        }
        foreach($element as $name => $value) {
            if(!isset($schema[$name])) {
                throw new Exception("'$name' field is not in the shema");
            }
            else if($value === null) {
                // no way to verify specific API consistency for now
                continue;
            }
            else if(gettype($value) != $schema[$name]) {
                $type = gettype($value);
                throw new Exception("'$name' field should of type '{$schema[$name]}', saw '$type' instead");
            }
        }
    }
}

################################################## Run tests

if(isset($argv[1])) {
    $path = "tests/{$argv[1]}.php";
    if(!file_exists($path)) {
        echo "\n'{$argv[1]}' test does not exist\n";
        exit;
    }
    require($path);
}
else {
    foreach(new DirectoryIterator('tests/') as $file) {
        if($file->isDot()) {
            continue;
        }
        require($file->getPathname());
    }
}