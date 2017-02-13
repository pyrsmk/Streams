<?php

namespace Streams;

use GuzzleHttp;

/*
    Base Reddit stream class
*/
abstract class Reddit extends AbstractStream {
    
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
        $this->guzzle = new GuzzleHttp\Client([
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36 OPR/42.0.2393.94'
            ]
        ]);
        parent::__construct($id, $config);
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
        $promise = $this->guzzle->getAsync("https://www.reddit.com$endpoint.json", ['query' => $query]);
        // Process data
        return $promise->then(function($response) {
            return json_decode($response->getBody(), true);
        });
    }
    
    /*
        Pagination
        
        Parameters
            string $endpoint
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _paginate($endpoint) {
        return $this->_createRequest($endpoint)->then(function($data) use($endpoint) {
            // Parse posts
            $elements = $this->_parsePosts($data['data']['children']);
            // Get remaining data
            $getNextPage = function($data) use($endpoint, &$getNextPage, &$elements) {
                if(isset($data['data']['after'])) {
                    $this->_createRequest($endpoint, [
                        'after' => $data['data']['after']
                    ])->then(function($data) use(&$getNextPage, &$elements) {
                        $elements = array_merge($elements, $this->_parsePosts($data['data']['children']));
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
            // NSFW verification
            if(!$this->config['nsfw'] && $post['data']['over_18']) {
                return;
            }
            // Prepare
            $id = $this->_getNewId();
            // Base
            $elements[$id] = [
                'date' => $post['data']['created'],
                'permalink' => $post['data']['url'],
                'title' => $post['data']['title'],
                'description' => null,
                'author' => $post['data']['author'],
                'avatar' => null
            ];
            // Text
            if(!isset($post['data']['preview'])) {
                $elements[$id]['type'] = 'text';
                $elements[$id]['description'] = htmlspecialchars_decode($post['data']['selftext_html']);
            }
            // Image
            else if($post['data']['media'] === null) {
                $elements[$id]['type'] = 'image';
                $elements[$id]['source'] = str_replace('&amp;', '&', $post['data']['preview']['images'][0]['source']['url']);
                $elements[$id]['width'] = $post['data']['preview']['images'][0]['source']['width'];
                $elements[$id]['height'] = $post['data']['preview']['images'][0]['source']['height'];
                // Get mime type
                $requests[] = function() use(&$elements, $id) {
                    return $this->_getMimetype($elements[$id]['source'])->then(function($mimetype) use(&$elements, $id) {
                        $elements[$id]['mimetype'] = $mimetype;
                    }, function() use(&$elements, $id) {
                        unset($elements[$id]);
                    });
                };
            }
            // Embed
            else {
                $elements[$id]['type'] = 'embed';
                $elements[$id]['html'] = htmlspecialchars_decode($post['data']['media']['oembed']['html']);
                $elements[$id]['width'] = $post['data']['media']['oembed']['width'];
                $elements[$id]['height'] = $post['data']['media']['oembed']['height'];
                $elements[$id]['preview'] = $post['data']['media']['oembed']['thumbnail_url'];
            }
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $this->_filter($elements);
    }
    
}