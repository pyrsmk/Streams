<?php

namespace Streams;

use GuzzleHttp;

/*
    Base DeviantArt stream class
*/
abstract class DeviantArt extends AbstractStream {
    
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
        if(!isset($config['api']) || !isset($config['secret'])) {
            throw new Exception("'api' and 'secret' parameters must be defined");
        }
        $this->guzzle = new GuzzleHttp\Client([
            'verify' => false,
            'timeout' => 2
        ]);
        parent::__construct($id, $config);
    }
    
    /*
        Return the allowed max results per page
        
        Return
            integer
    */
    protected function _getMaxResultsPerPage() {
        return 24;
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
        // Prepare headers to avoid DeviantArt 504 error
        $headers = [
            'Host' => 'www.deviantart.com',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36 OPR/42.0.2393.517',
            'Upgrade-Insecure-Requests' => 1,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
            'Accept-Language' => 'fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4'
        ];
        // Get access token
        if(!isset($this->token)) {
            $this->guzzle->getAsync('https://www.deviantart.com/oauth2/token', [
                'query' => [
                    'client_id' => $this->config['api'],
                    'client_secret' => $this->config['secret'],
                    'grant_type' => 'client_credentials'
                ],
                'headers' => $headers
            ])->then(function($response) {
                $data = json_decode($response->getBody(), true);
                if(isset($data['error'])) {
                    throw new Exception($data['error_description']);
                }
                $this->token = $data['access_token'];
            })->wait();
        }
        // Define request
        $query['access_token'] = $this->token;
        $promise = $this->guzzle->getAsync("https://www.deviantart.com/api/v1/oauth2$endpoint", [
            'query' => $query,
            'headers' => $headers
        ]);
        // Process data
        return $promise->then(function($response) {
            return json_decode($response->getBody(), true);
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
            $elements = $this->_parsePosts($data['results']);
            // Load remaining data
            if($this->config['limit'] === null || count($elements) < $this->config['limit']) {
                // Prepare
                $requests = [];
                $start = count($elements) + 1;
                if($this->config['limit'] === null) {
                    $end = $data['estimated_total'];
                }
                else {
                    $end = $this->config['limit'] < $data['estimated_total'] ? $this->config['limit'] : $data['estimated_total'];
                }
                // Create further requests
                for($offset=$start, $j=$end; $offset<$j; $offset+=$this->per_page) {
                    $requests[] = function() use($endpoint, $query, &$elements, $offset) {
                        // Set offset
                        $query['offset'] = $offset;
                        // Add new request
                        return $this->_createRequest($endpoint, $query)->then(function($data) use(&$elements) {
                            foreach($this->_parsePosts($data['results']) as $id => $element) {
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
            array $data
        
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
                'title' => $post['title'],
                'date' => $post['published_time'],
                'permalink' => $post['url'],
                'author' => $post['author']['username'],
                'avatar' => $post['author']['usericon']
            ];
            // Text
            if(isset($post['excerpt'])) {
                $elements[$id]['type'] = 'text';
                $requests[] = function() use($post, &$elements, $id) {
                    return $this->_createRequest('/deviation/content', [
                        'deviationid' => $post['deviationid']
                    ])->then(function($data) use(&$elements, $id) {
                        $elements[$id]['description'] = $this->_formatDescription($data['html']);
                    }, function() use(&$elements, $id) {
                        unset($elements[$id]);
                    });
                };
            }
            // Image
            else {
                $elements[$id]['type'] = 'image';
                $elements[$id]['source'] = $post['content']['src'];
                $elements[$id]['width'] = $post['content']['width'];
                $elements[$id]['height'] = $post['content']['height'];
                $elements[$id]['description'] = null;
                $requests[] = function() use(&$elements, $id) {
                    return $this->_getMimetype($elements[$id]['source'])->then(function($mimetype) use(&$elements, $id) {
                        $elements[$id]['mimetype'] = $mimetype;
                    }, function() use(&$elements, $id) {
                        unset($elements[$id]);
                    });
                };
            }
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $this->_filter($elements);
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
                            '/\\<br \\/\\>/',
                            '/<script\b[^>]*>(.*?)<\\/script>/is'
                        ],
                        [
                            "\n",
                            ''
                        ],
                        $description
                    )
                )
            )
        );
    }
    
}