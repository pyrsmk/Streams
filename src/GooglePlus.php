<?php

namespace Streams;

use GuzzleHttp;

/*
    Base GooglePlus stream class
*/
abstract class GooglePlus extends AbstractStream {
    
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
        if(!isset($config['api'])) {
            throw new Exception("'api' parameter must be defined");
        }
        $this->guzzle = new GuzzleHttp\Client(['verify' => false]);
        parent::__construct($id, $config);
    }
    
    /*
        Return the allowed max results per page
        
        Return
            integer
    */
    protected function _getMaxResultsPerPage() {
        return 100;
    }
    
    /*
        Create a request
        
        Parameters
            string $endpoint
            array $query
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _createRequest($endpoint, array $query = []) {
        // Define request
        $promise = $this->guzzle->getAsync("https://www.googleapis.com/plus/v1$endpoint", [
            'query' => array_merge(
                ['key' => $this->config['api']],
                $query
            )
        ]);
        // Process data
        return $promise->then(function($response) {
            return json_decode($response->getBody(), true);
        });
    }
    
    /*
        Pagination
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _paginate($endpoint, $query) {
        return $this->_createRequest($endpoint, $query)->then(function($data) use($endpoint, $query) {
            // Parse posts
            $elements = $this->_parsePosts($data['items']);
            // Get remaining data
            $getNextPage = function($data) use($endpoint, $query, &$getNextPage, &$elements) {
                if(isset($data['nextPageToken'])) {
                    $query['pageToken'] = $data['nextPageToken'];
                    $this->_createRequest($endpoint, $query)->then(function($data) use(&$getNextPage, &$elements) {
                        $elements = array_merge($elements, $this->_parsePosts($data['items']));
                        if($this->config['limit'] === null || count($elements) < $this->config['limit']) {
                            $getNextPage($data);
                        }
                    })->wait();
                }
            };
            $getNextPage($data);
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
                'date' => strtotime($post['published']),
                'permalink' => $post['url'],
                'author' => $post['actor']['displayName'],
                'avatar' => $post['actor']['image']['url'],
                'title' => $post['title'],
                'description' => $post['object']['content']
            ];
            // Text
            if(!isset($post['object']['attachments'][0]['objectType']) || $post['object']['attachments'][0]['objectType'] == 'article') {
                $elements[$id]['type'] = 'text';
            }
            // Image
            else if($post['object']['attachments'][0]['objectType'] == 'photo') {
                $elements[$id]['type'] = 'image';
                $elements[$id]['source'] = $post['object']['attachments'][0]['fullImage']['url'];
                $requests[] = function() use($post, &$elements, $id) {
                    $url = $post['object']['attachments'][0]['fullImage']['url'];
                    return $this->_getMimetype($url)->then(function($mimetype) use(&$elements, $id) {
                        $elements[$id]['mimetype'] = $mimetype;
                    }, function() use(&$elements, $id) {
                        unset($elements[$id]);
                    });
                };
                $requests[] = function() use($post, &$elements, $id) {
                    return $this->_getImageSize($elements[$id]['source'])->then(function($size) use(&$elements, $id) {
                        $elements[$id]['width'] = $size['width'];
                        $elements[$id]['height'] = $size['height'];
                    }, function() use(&$elements, $id) {
                        unset($elements[$id]);
                    });
                };
            }
            // Embed
            else if(isset($post['object']['attachments'][0]['embed'])) {
                $elements[$id]['type'] = 'embed';
                $elements[$id]['html'] = "<iframe src=\"{$post['object']['attachments'][0]['embed']['url']}\" width=\"{$post['object']['attachments'][0]['image']['width']}\" height=\"{$post['object']['attachments'][0]['image']['height']}\"></iframe>";
                $elements[$id]['width'] = $post['object']['attachments'][0]['image']['width'];
                $elements[$id]['height'] = $post['object']['attachments'][0]['image']['height'];
                $elements[$id]['preview'] = $post['object']['attachments'][0]['image']['url'];
            }
            // Unsupported/invalid
            else {
                unset($elements[$id]);
            }
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $elements;
    }
    
}