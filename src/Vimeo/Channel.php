<?php

namespace Streams\Vimeo;

use Streams\Vimeo;

/*
    Vimeo channel stream
*/
class Channel extends Vimeo {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/channels/$this->id/videos");
    }
    
}