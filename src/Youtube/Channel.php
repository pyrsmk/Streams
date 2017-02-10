<?php

namespace Streams\Youtube;

use Streams\Youtube;
use Streams\Exception;
use GuzzleHttp;

/*
    Youtube channel stream
*/
class Channel extends Youtube {
    
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
        return $this->_paginate('/search', [
            'maxResults' => $this->per_page,
            'channelId' => $this->id,
            'part' => 'snippet',
            'type' => 'video',
            'order' => 'date',
            'safeSearch' => $this->config['nsfw'] ? 'none' : 'strict'
        ], $avatar);
    }
    
}