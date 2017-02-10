<?php

namespace Streams\DeviantArt;

use Streams\DeviantArt;
use Streams\Exception;
use GuzzleHttp;

/*
    DeviantArt gallery stream
*/
class Gallery extends DeviantArt {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/gallery/all", [
            'username' => $this->id,
            'limit' => $this->per_page,
            'mode' => 'newest',
            'mature_content' => $this->config['nsfw']
        ]);
    }
    
}