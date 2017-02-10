<?php

namespace Streams\Flickr;

use Streams\Flickr;

/*
    Flickr user stream
*/
class User extends Flickr {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Get user id
        $user_id = null;
        $this->_getNSID($this->id)->then(function($nsid) use(&$user_id) {
            $user_id = $nsid;
        })->wait();
        // Get data
        return $this->_paginate([
            'method' => 'flickr.people.getPublicPhotos',
            'user_id' => $user_id,
            'extras' => 'date_upload,url_l,media,owner_name,icon_server',
            'per_page' => $this->per_page
        ], 'photos');
    }
    
}