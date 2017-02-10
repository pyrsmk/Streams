<?php

namespace Streams\Instagram;

use Streams\Instagram;

/*
    Instagram user stream
*/
class User extends Instagram {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_createRequest("/$this->id/media/")->then(function($data) {
            return $this->_parsePosts($data['items']);
        });
    }
    
}