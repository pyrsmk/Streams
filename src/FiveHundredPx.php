<?php

namespace Streams;

use GuzzleHttp;

/*
    Base 500px stream class
*/
abstract class FiveHundredPx extends AbstractStream {
    
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
        // Define how many results per page
        $query['rpp'] = $this->per_page;
        // Define request
        $query['consumer_key'] = $this->config['api'];
        $promise = $this->guzzle->getAsync("https://api.500px.com/v1$endpoint", [
            'query' => $query
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
            $elements = $this->_parsePosts($data['photos']);
            // Load remaining data
            if($this->config['limit'] === null || count($elements) < $this->config['limit']) {
                // Prepare
                $requests = [];
                $start = count($elements) + 1;
                if($this->config['limit'] === null) {
                    $end = $data['total_items'];
                }
                else {
                    $end = $this->config['limit'] < $data['total_items'] ? $this->config['limit'] : $data['total_items'];
                }
                $end = ceil($end/$this->per_page);
                // Create further requests
                for($page=2, $j=$end; $page<$j; ++$page) {
                    $requests[] = function() use($endpoint, $query, &$elements, $page) {
                        // Set page
                        $query['page'] = $page;
                        // Add new request
                        return $this->_createRequest($endpoint, $query)->then(function($data) use(&$elements) {
                            $posts = $this->_parsePosts($data['photos']);
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
            // NSFW
            if($this->config['nsfw'] && $post['nsfw']) {
                continue;
            }
            // Image
            $elements[$id] = [
                'type' => 'image',
                'date' => strtotime($post['created_at']),
                'permalink' => 'https://500px.com'.$post['url'],
                'title' => $post['name'],
                'description' => $post['description'],
                'author' => $post['user']['username'],
                'avatar' => $post['user']['userpic_url']
            ];
            // Get source
            $requests[] = function() use($post, &$elements, $id) {
                return $this->_getImage($post['id'])->then(function($image) use(&$elements, $id) {
                    if(isset($elements[$id])) {
                        $elements[$id]['source'] = $image['source'];
                        $elements[$id]['width'] = $image['width'];
                        $elements[$id]['height'] = $image['height'];
                    }
                }, function() use(&$elements, $id) {
                    if(isset($elements[$id])) {
                        unset($elements[$id]);
                    }
                });
            };
            // Get mime type
            $requests[] = function() use($post, &$elements, $id) {
                return $this->_getMimetype($post['image_url'])->then(function($mimetype) use(&$elements, $id) {
                    if(isset($elements[$id])) {
                        $elements[$id]['mimetype'] = $mimetype;
                    }
                }, function() use(&$elements, $id) {
                    if(isset($elements[$id])) {
                        unset($elements[$id]);
                    }
                });
            };
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $this->_filter($elements);
    }
    
    /*
        Get image details
        
        Parameters
            string $id
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getImage($id) {
        return $this->_createRequest("/photos/$id")->then(function($data) {
            $image = ['source' => $data['photo']['image_url']];
            $this->_getImageSize($data['photo']['image_url'])->then(function($size) use(&$image) {
                $image['width'] = $size['width'];
                $image['height'] = $size['height'];
            })->wait();
            return $image;
        });
    }
    
    /*
        Get a user ID
        
        Parameters
            string $name
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getUserId($name) {
        return $this->_createRequest('/users/search', ['term' => $name])->then(function($data) use($name) {
            if(!count($data['users'])) {
                throw new Exception("'$name' user ID not found");
            }
            return $data['users'][0]['id'];
        });
    }
    
}