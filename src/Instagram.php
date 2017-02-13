<?php

namespace Streams;

use GuzzleHttp;

/*
    Base Instagram stream class
*/
abstract class Instagram extends AbstractStream {
    
    /*
        GuzzleHttp\Client $guzzle
    */
    protected $guzzle;
    
    /*
        Construct stream
        
        Parameters
            string $key
    */
    public function __construct($id, array $config = []) {
        $this->guzzle = new GuzzleHttp\Client(['verify' => false]);
        parent::__construct($id, $config);
    }
    
    /*
        Create a request
        
        Parameters
            string $endpoint
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _createRequest($endpoint) {
        // Define request
        $promise = $this->guzzle->getAsync("https://www.instagram.com$endpoint");
        // Process data
        return $promise->then(function($response) {
            return json_decode($response->getBody(), true);
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
            }
            // Video
            else if($post['type'] == 'video') {
                $elements[$id]['type'] = 'video';
                $elements[$id]['source'] = $post['videos']['standard_resolution']['url'];
                $elements[$id]['width'] = $post['videos']['standard_resolution']['width'];
                $elements[$id]['height'] = $post['videos']['standard_resolution']['height'];
                $elements[$id]['preview'] = $post['images']['standard_resolution']['url'];
            }
            // Get mime type
            $requests[] = function() use(&$elements, $id) {
                return $this->_getMimetype($elements[$id]['source'])->then(function($mimetype) use(&$elements, $id) {
                    $elements[$id]['mimetype'] = $mimetype;
                }, function() use(&$elements, $id) {
                    unset($elements[$id]);
                });
            };
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $this->_filter($elements);
    }
    
}