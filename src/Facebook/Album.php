<?php

namespace Streams\Facebook;

use Streams\Facebook;
use Streams\Exception;
use GuzzleHttp;

/*
    Facebook album stream
*/
class Album extends Facebook {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Prepare
        $query = [];
        if(isset($this->config['type'])) {
            $query['type'] = $this->config['type'];
        }
        $query['fields'] = 'created_time,caption,link,images';
        // Load the first page
        return $this->_paginate("/$this->id/photos", $query);
    }
    
    /*
        Parse posts and create elements
        
        Parameters
            array $posts
        
        Return
            array
    */
    protected function _parsePosts($posts) {
        // Prepare
        $elements = [];
        $requests = [];
        // Get page name
        $author = null;
        $this->_getPageName($this->id)->then(function($name) use(&$author) {
            $author = $name;
        })->wait();
        // Browse posts
        foreach($posts as $post) {
            // Prepare
            $id = $this->_getNewId();
            // Find the wider registered image
            $w = 0;
            $index = 0;
            foreach($post['images'] as $i => $image) {
                if($image['width'] > $w) {
                    $w = $image['width'];
                    $index = $i;
                }
            }
            // Add image
            $elements[$id] = [
                'type' => 'image',
                'date' => $post['created_time']->getTimestamp(),
                'source' => $post['images'][$index]['source'],
                'permalink' => $post['link'],
                'title' => isset($post['caption']) ? $post['caption'] : null,
                'description' => null,
                'width' => $post['images'][$index]['width'],
                'height' => $post['images'][$index]['height'],
                'author' => $author,
                'avatar' => "http://graph.facebook.com/$this->id/picture?type=large"
            ];
            // Get mimetype
            $requests[] = function() use($post, &$elements, $id, $index) {
                $url = $post['images'][$index]['source'];
                return $this->_getMimetype($url)->then(function($mimetype) use(&$elements, $id) {
                    $elements[$id]['mimetype'] = $mimetype;
                }, function() use(&$elements, $id) {
                    unset($elements[$id]);
                });
            };
        }
        // Populate last fields
        $this->guzzle = new GuzzleHttp\Client(['verify' => false]);
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $this->_filterTypes($elements);
    }
    
}