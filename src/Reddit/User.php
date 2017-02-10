<?php

namespace Streams\Reddit;

use Streams\Reddit;

/*
    Reddit user stream
*/
class User extends Reddit {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/user/$this->id/submitted");
    }
    
}