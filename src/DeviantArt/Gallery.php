<?php

namespace Streams\DeviantArt;

use Streams\DeviantArt;

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
            'mode' => 'newest'
        ]);
    }
    
}