<?php

namespace Streams\Vimeo;

use Streams\Vimeo;

/*
    Vimeo group stream
*/
class Group extends Vimeo {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/groups/$this->id/videos");
    }
    
}