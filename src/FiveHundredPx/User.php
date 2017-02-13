<?php

namespace Streams\FiveHundredPx;

use Streams\FiveHundredPx;

/*
    500px user stream
*/
class User extends FiveHundredPx {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate('/photos', [
            'feature' => 'user',
            'username' => $this->id
        ]);
    }
    
}