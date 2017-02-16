<?php

namespace Streams\Flickr;

use Streams\Flickr;
use Streams\Exception;

/*
    Flickr album stream
*/
class Album extends Flickr {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Parse provided id
        list($name, $album) = preg_split('/\//', $this->id);
        if(!$name || !$album) {
            throw new Exception("'id' parameter is malformed");
        }
        // Get user id
        $user_id = null;
        $this->_getNSID($name)->then(function($nsid) use(&$user_id) {
            $user_id = $nsid;
        })->wait();
        // Get data
        return $this->_paginate([
            'method' => 'flickr.photosets.getPhotos',
            'user_id' => $user_id,
            'photoset_id' => $album,
            'extras' => 'date_upload,url_l,media,icon_server',
            'per_page' => $this->per_page
        ], 'photoset');
    }
    
}