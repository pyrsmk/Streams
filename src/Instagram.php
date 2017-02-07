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
    
}