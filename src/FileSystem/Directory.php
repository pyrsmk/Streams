<?php

namespace Streams\FileSystem;

use Streams\FileSystem;
use Streams\Exception;

/*
    Filesystem directory stream
*/
class Directory extends FileSystem {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_scanDirectory($this->id)->then(function($files) {
            // Prepare
            $elements = [];
            // Browse files
            foreach($files as $file) {
                // Prepare
                $id = $this->_getNewId();
                // Read metadata
                $id3 = new \getID3();
                $info = $id3->analyze($file->getPathname());
                // Set type
                if(strpos($info['mime_type'], 'image') === 0) {
                    $type = 'image';
                }
                else if(strpos($info['mime_type'], 'video') === 0) {
                    $type = 'video';
                }
                else {
                    continue;
                }
                // Add new element
                $elements[$id] = [
                    'type' => $type,
                    'date' => $file->getATime(),
                    'source' => str_replace('\\', '/', $file->getPathname()),
                    'title' => $file->getFilename(),
                    'width' => $info['video']['resolution_x'],
                    'height' => $info['video']['resolution_y'],
                    'mimetype' => $info['mime_type'],
                    'author' => null,
                    'avatar' => null,
                    'description' => null,
                    'permalink' => null,
                ];
                if($type == 'video') {
                    $elements[$id]['preview'] = null;
                }
                // Limit elements
                if($this->config['limit'] !== null && count($elements) == $this->config['limit']) {
                    break;
                }
            }
            return $elements;
        });
    }
    
}