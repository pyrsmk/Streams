<?php

namespace Streams\Dribbble;

use Streams\Dribbble;

/*
    Dribbble bucket stream
*/
class Bucket extends Dribbble {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/buckets/$this->id/shots");
    }
    
}