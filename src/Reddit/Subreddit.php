<?php

namespace Streams\Reddit;

use Streams\Reddit;

/*
    Reddit subreddit stream
*/
class Subreddit extends Reddit {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Prepare
        $type = isset($this->config['type']) ? $this->config['type'] : 'new';
        if($type == 'popular') {
            $type = '';
        }
        // Get elements
        return $this->_paginate("/r/$this->id/$type");
    }
    
}