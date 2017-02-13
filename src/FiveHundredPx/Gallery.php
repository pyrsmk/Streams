<?php

namespace Streams\FiveHundredPx;

use Streams\FiveHundredPx;
use Streams\Exception;


/*
    500px gallery stream
*/
class Gallery extends FiveHundredPx {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Extract parameters
        list($username, $gallery) = explode('/', $this->id);
        if(!$username || !$gallery) {
            throw new Exception("Bad '$this->id' format");
        }
        // Get user ID
        $user_id = null;
        $this->_getUserId($username)->then(function($id) use(&$user_id) {
            $user_id = $id;
        })->wait();
        // Get data
        return $this->_paginate("/users/$user_id/galleries/$gallery/items");
    }
    
}