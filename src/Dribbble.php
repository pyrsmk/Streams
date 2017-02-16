<?php

namespace Streams;

use GuzzleHttp;

/*
    Base Dribbble stream class
    
    API
        http://developer.dribbble.com/v1/
*/
abstract class Dribbble extends AbstractStream {
    
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
        if(!isset($config['token'])) {
            throw new Exception("'token' parameter must be defined");
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
        // Define how many results per page
        $query['per_page'] = $this->per_page;
        // Define request
        $promise = $this->guzzle->getAsync("https://api.dribbble.com/v1$endpoint", [
            'query' => $query,
            'headers' => [
                'Authorization' => 'Bearer '.$this->config['token']
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
            string $avatar
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _paginate($endpoint, array $query = [], $avatar = null) {
        return $this->_createRequest($endpoint, $query)->then(function($data) use($endpoint, $query, $avatar) {
            // Parse posts
            $elements = $this->_parsePosts($data, $avatar);
            // Get remaining data
            $page = 1;
            $getNextPage = function($data) use($endpoint, $query, $avatar, &$page, &$getNextPage, &$elements) {
                if(empty($this->config['limit']) || count($elements) < $this->config['limit']) {
                    $query['page'] = ++$page;
                    $this->_createRequest($endpoint, $query)->then(function($data) use($avatar, &$getNextPage, &$elements) {
                        if(count($data)) {
                            $elements = array_merge($elements, $this->_parsePosts($data, $avatar));
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
            string $avatar
        
        Return
            array
    */
    protected function _parsePosts($posts, $avatar = null) {
        // Prepare
        $elements = [];
        $requests = [];
        // Browse posts
        foreach($posts as $post) {
            // Prepare
            $id = $this->_getNewId();
            // Image
            $elements[$id] = [
                'type' => 'image',
                'date' => strtotime($post['created_at']),
                'permalink' => $post['html_url'],
                'title' => $post['title'],
                'description' => $this->_formatDescription($post['description']),
                'source' => $post['images']['hidpi'],
                'width' => $post['width'],
                'height' => $post['height'],
                'author' => $this->id,
                'avatar' => isset($post['user']['avatar_url']) ? $post['user']['avatar_url'] : $avatar
            ];
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
    
    /*
        Get a user avatar
        
        Parameters
            string $id
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getAvatar($id) {
        return $this->_createRequest("/users/$id")->then(function($data) {
            return $data['avatar_url'];
        });
    }
    
    /*
        Format a description
        
        Parameters
            string $description
        
        Return
            string
    */
    protected function _formatDescription($description) {
        return trim(
            html_entity_decode(
                strip_tags(
                    preg_replace(
                        [
                            '/<a.*?>.*?<\\/a>/',
                            '/[|]/'
                        ],
                        [
                            '',
                            ''
                        ],
                        $description
                    )
                )
            )
        );
    }
    
}