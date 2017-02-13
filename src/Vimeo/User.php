<?php

namespace Streams\Vimeo;

use Streams\Vimeo;

/*
    Vimeo user stream
*/
class User extends Vimeo {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/users/$this->id/videos");
    }
    
}