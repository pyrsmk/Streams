<?php

namespace Streams\Youtube;

use Streams\Youtube;
use Streams\Exception;
use GuzzleHttp;

/*
    Youtube playlist stream
*/
class Playlist extends Youtube {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate('/playlistItems', [
            'maxResults' => $this->per_page,
            'playlistId' => $this->id,
            'part' => 'snippet'
        ]);
    }
    
}