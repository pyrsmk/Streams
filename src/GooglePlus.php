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
    
}