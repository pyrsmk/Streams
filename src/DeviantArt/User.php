<?php

namespace Streams\DeviantArt;

use Streams\DeviantArt;

/*
    DeviantArt user stream
*/
class User extends DeviantArt {
    
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