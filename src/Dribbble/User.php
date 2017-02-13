<?php

namespace Streams\Dribbble;

use Streams\Dribbble;

/*
    Dribbble user stream
*/
class User extends Dribbble {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Get avatar
        $avatar = null;
        $this->_getAvatar($this->id)->then(function($url) use(&$avatar) {
            $avatar = $url;
        })->wait();
        // Get data
        return $this->_paginate("/users/$this->id/shots", [], $avatar);
    }
    
}