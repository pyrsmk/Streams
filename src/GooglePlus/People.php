<?php

namespace Streams\GooglePlus;

use Streams\GooglePlus;

/*
    Youtube channel stream
*/
class People extends GooglePlus {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate(
            "/people/$this->id/activities/public",
            ['maxResults' => $this->per_page]
        );
    }
    
}