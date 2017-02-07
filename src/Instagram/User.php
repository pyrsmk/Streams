<?php

namespace Streams\Instagram;

use Streams\Instagram;
use GuzzleHttp;

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
            // Parse posts
            $elements = $this->_parsePosts($data['items']);
            // Limit elements
            if($this->config['limit'] !== null && count($elements) > $this->config['limit']) {
                $elements = array_slice($elements, 0, $this->config['limit']);
            }
            return $elements;
        });
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
        // Browse posts
        foreach($posts as $post) {
            // Prepare
            $id = $this->_getNewId();
            // Base
            $elements[$id] = [
                'date' => $post['created_time'],
                'permalink' => $post['link'],
                'title' => $post['caption']['text'],
                'description' => null,
                'author' => $post['caption']['from']['full_name'],
                'avatar' => $post['caption']['from']['profile_picture']
            ];
            // Image
            if($post['type'] == 'image') {
                $elements[$id]['type'] = 'image';
                $elements[$id]['source'] = $post['images']['standard_resolution']['url'];
                $elements[$id]['width'] = $post['images']['standard_resolution']['width'];
                $elements[$id]['height'] = $post['images']['standard_resolution']['height'];
                $elements[$id]['mimetype'] = mimetype($elements[$id]['source']);
            }
            // Video
            else if($post['type'] == 'video') {
                $elements[$id]['type'] = 'video';
                $elements[$id]['source'] = $post['videos']['standard_resolution']['url'];
                $elements[$id]['width'] = $post['videos']['standard_resolution']['width'];
                $elements[$id]['height'] = $post['videos']['standard_resolution']['height'];
                $elements[$id]['preview'] = $post['images']['standard_resolution']['url'];
                $elements[$id]['mimetype'] = mimetype($elements[$id]['source']);
            }
            // Get mime type
            $requests[] = function() use(&$elements, $id) {
                return $this->_getMimetype($elements[$id]['source'])->then(function($mimetype) use(&$elements, $id) {
                    $elements[$id]['mimetype'] = $mimetype;
                });
            };
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $elements;
    }
    
}