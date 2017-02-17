<?php

namespace Streams;

use GuzzleHttp;
use Closure;

/*
    Stream abstract class
*/
abstract class AbstractStream {
    
    /*
        string $id
        array $config
        integer $per_page
    */
    protected $id;
    protected $config;
    protected $per_page;
    
    /*
        Construct stream
        
        Parameters
            string $key
    */
    public function __construct($id, array $config = []) {
        // Prepare
        $types = ['text', 'image', 'video', 'embed'];
        // Register vars
        $this->id = $id;
        $this->config = $config;
        // Init config
        $this->config['get'] = isset($this->config['get']) ? $this->config['get'] : $types;
        foreach($this->config['get'] as $type) {
            if(!in_array($type, $types)) {
                throw new Exception("Invalid '$type' type in 'get' parameter");
            }
        }
        $this->config['nsfw'] = isset($this->config['nsfw']) ? $this->config['nsfw'] : false;
        $this->config['limit'] = isset($this->config['limit']) ? $this->config['limit'] : false;
        $this->config['mimetype'] = isset($this->config['mimetype']) ? $this->config['mimetype'] : false;
        // Init per_page var
        $max_results = $this->_getMaxResultsPerPage();
        if($max_results !== null) {
            if(empty($this->config['limit']) || $this->config['limit'] > $max_results) {
                $this->per_page = $max_results;
            }
            else {
                $this->per_page = $this->config['limit'];
            }
        }
    }
    
    /*
        Return the allowed max results per page
        
        Return
            integer
    */
    protected function _getMaxResultsPerPage() {
        return null;
    }
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    public function get() {
        return $this->_getElements()->then(function($elements) {
            // Limit elements
            if(!empty($this->config['limit']) && count($elements) > $this->config['limit']) {
                $elements = array_slice($elements, 0, $this->config['limit']);
            }
            // Return elements
            return $elements;
        });
    }
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    abstract protected function _getElements();
    
    /*
        Filter elements according to 'get' config parameter
        
        Parameters
            array $elements
        
        Return
            array
    */
    protected function _filter(array $elements) {
        foreach($elements as $id => $element) {
            if(!in_array($element['type'], $this->config['get'])) {
                unset($elements[$id]);
            }
        }
        return $elements;
    }
    
    /*
        Generate a new ID for an element
        
        Return
            string
    */
    protected function _getNewId() {
        return md5(uniqid(rand(), true));
    }
    
    /*
        Get the mime type of a remote file
        
        Parameters
            string $url
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getMimetype($url) {
        // Init Guzzle
        if(property_exists($this, 'guzzle')) {
            $guzzle = $this->guzzle;
        }
        else {
            $guzzle = new GuzzleHttp\Client(['verify' => false]);
        }
        // Define request
        $promise = $this->guzzle->getAsync($url, [
            'curl' => [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY => true,
                CURLOPT_TIMEOUT => 2
            ]
        ]);
        // Process data
        return $promise->then(function($response) {
            $values = $response->getHeader('Content-Type');
            if(!count($values)) {
                throw new Exception("No content type returned");
            }
            return $values[0];
        });
    }
    
}