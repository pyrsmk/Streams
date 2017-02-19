<?php

namespace Streams;

use GuzzleHttp;

/*
    Base Vimeo stream class
    
    API
        https://developer.vimeo.com/api/endpoints
*/
abstract class Vimeo extends AbstractStream {
    
    /*
        GuzzleHttp\Client $guzzle
        string $token
    */
    protected $guzzle;
    protected $token;
    
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
        // Get an access token
        if(!isset($this->token)) {
            $this->guzzle->postAsync('https://api.vimeo.com/oauth/authorize/client', [
                'query' => [
                    'grant_type' => 'client_credentials'
                ],
                'headers' => [
                    'Authorization' => 'basic '.base64_encode($this->config['api'].':'.$this->config['secret'])
                ]
            ])->then(function($response) {
                $data = json_decode($response->getBody(), true);
                $this->token = $data['access_token'];
            })->wait();
        }
        // NSFW
        if(!$this->config['nsfw']) {
            $query['filter'] = 'content_rating';
            $query['filter_content_rating'] = 'safe,unrated';
        }
        // Define how many results per page
        $query['per_page'] = $this->per_page;
        // Define request
        $promise = $this->guzzle->getAsync("https://api.vimeo.com$endpoint", [
            'query' => $query,
            'headers' => [
                'Authorization' => 'Bearer '.$this->token
            ]
        ]);
        // Process data
        return $promise->then(function($response) {
            $data = json_decode($response->getBody(), true);
            return $data;
        });
    }
    
    /*
        Pagination
        
        Parameters
            string $endpoint
            array $query
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _paginate($endpoint, array $query = []) {
        return $this->_createRequest($endpoint, $query)->then(function($data) use($endpoint, $query) {
            // Parse posts
            $elements = $this->_parsePosts($data['data']);
            // Load remaining data
            if(empty($this->config['limit']) || count($elements) < $this->config['limit']) {
                // Prepare
                $requests = [];
                $start = count($elements) + 1;
                if(empty($this->config['limit'])) {
                    $end = $data['total'];
                }
                else {
                    $end = $this->config['limit'] < $data['total'] ? $this->config['limit'] : $data['total'];
                }
                $end = ceil($end/$this->per_page);
                // Create further requests
                for($page=2, $j=$end; $page<$j; ++$page) {
                    $requests[] = function() use($endpoint, $query, &$elements, $page) {
                        // Set page
                        $query['page'] = $page;
                        // Add new request
                        return $this->_createRequest($endpoint, $query)->then(function($data) use(&$elements) {
                            $posts = $this->_parsePosts($data['data']);
                            foreach($posts as $id => $element) {
                                $elements[$id] = $element;
                            }
                        });
                    };
                }
                // Run all requests
                $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
                $pool->promise()->wait();
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
            $preview = $post['pictures']['sizes'][count($post['pictures']['sizes']) - 1]['link'];
            preg_match('/^.+?(\d+)x(\d+)\.\w{3}\?r=pad$/', $preview, $size);
            // Embed
            $elements[$id] = [
                'type' => 'embed',
                'date' => strtotime($post['created_time']),
                'permalink' => $post['link'],
                'title' => $post['name'],
                'description' => $post['description'],
                'html' => $post['embed']['html'],
                'width' => $post['width'],
                'height' => $post['height'],
                'preview' => [
                    'source' => $preview,
                    'width' => (int)$size[1],
                    'height' => (int)$size[2]
                ],
                'author' => $post['user']['name'],
                'avatar' => $post['user']['pictures']['sizes'][count($post['user']['pictures']['sizes']) - 1]['link']
            ];
        }
        return $this->_filter($elements);
    }
    
}