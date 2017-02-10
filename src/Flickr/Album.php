<?php

namespace Streams\Flickr;

use Streams\Flickr;
use Streams\Exception;
use GuzzleHttp;

/*
    Flickr album stream
*/
class Album extends Flickr {
    
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
        // Parse provided id
        list($name, $album) = preg_split('/\//', $this->id);
        if(!$name || !$album) {
            throw new Exception("'id' parameter is malformed");
        }
        // Get user id
        $this->_getNSID($name)->then(function($nsid) {
            $this->user_id = $nsid;
        })->wait();
        // Load the first page
        return $this->_createRequest([
            'method' => 'flickr.photosets.getPhotos',
            'user_id' => $this->user_id,
            'photoset_id' => $album,
            'extras' => 'date_upload,url_l,media,icon_server',
            'per_page' => $this->per_page
        ])->then(function($data) use($per_page) {
            // Parse posts
            $elements = $this->_parsePosts($data['photoset']['photo'], $data['photoset']['ownername']);
            // Prepare
            $requests = [];
            if($this->config['limit'] === null) {
                $remaining = $data['photoset']['total'] - count($data['photoset']['photo']);
            }
            else {
                $remaining = $this->config['limit'] - count($data['photoset']['photo']);
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
                        $posts = $this->_parsePosts($data['photoset']['photo'], $data['photoset']['ownername']);
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