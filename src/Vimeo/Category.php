<?php

namespace Streams\Vimeo;

use Streams\Vimeo;

/*
    Vimeo category stream
*/
class Category extends Vimeo {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/categories/$this->id/videos");
    }
    
}