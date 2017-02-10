<?php

namespace Streams\Flickr;

use Streams\Flickr;
use Streams\Exception;
use GuzzleHttp;

/*
    Flickr user stream
*/
class User extends Flickr {
    
    /*
        string $user_id
    */
    protected $user_id;
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Get user id
        $this->_getNSID($this->id)->then(function($nsid) {
            $this->user_id = $nsid;
        })->wait();
        // Load the first page
        return $this->_createRequest([
            'method' => 'flickr.people.getPublicPhotos',
            'user_id' => $this->user_id,
            'extras' => 'date_upload,url_l,media,owner_name,icon_server',
            'per_page' => $this->per_page
        ])->then(function($data) use($per_page) {
            // Parse posts
            $elements = $this->_parsePosts($data['photos']['photo'], $data['photos']['photo'][0]['ownername']);
            // Prepare
            $requests = [];
            if($this->config['limit'] === null) {
                $remaining = $data['photos']['total'] - count($data['photos']['photo']);
            }
            else {
                $remaining = $this->config['limit'] - count($data['photos']['photo']);
            }
            // Get remaining data
            for($i=2, $j=ceil($remaining/$per_page)+1; $i<$j+1; ++$i) {
                $requests[] = function() use(&$elements, $per_page, $i) {
                    return $this->_createRequest([
                        'method' => 'flickr.people.getPublicPhotos',
                        'user_id' => $this->user_id,
                        'extras' => 'date_upload,url_l,media,owner_name,icon_server',
                        'per_page' => $per_page,
                        'page' => $i
                    ])->then(function($data) use(&$elements) {
                        $posts = $this->_parsePosts($data['photos']['photo'], $data['photos']['photo'][0]['ownername']);
                        foreach($posts as $id => $element) {
                            $elements[$id] = $element;
                        }
                    });
                };
            }
            $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
            $pool->promise()->wait();
            return $elements;
        });
    }
    
}