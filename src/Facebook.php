<?php

namespace Streams;

use GuzzleHttp\Promise\Promise;

/*
    Base Facebook stream class
    
    API
        https://developers.facebook.com/docs/graph-api/reference/
*/
abstract class Facebook extends AbstractStream {
    
    /*
        Facebook\Facebook $facebook
    */
    protected $facebook;
    
    /*
        Construct stream
        
        Parameters
            string $key
    */
    public function __construct($id, array $config = []) {
        // Verify
        if(!isset($config['api']) || !isset($config['secret'])) {
            throw new Exception("'api' and 'secret' parameters must be defined");
        }
        // Create Facebook connection
        $this->facebook = new \Facebook\Facebook([
            'app_id' => $config['api'],
            'app_secret' => $config['secret'],
            'default_access_token' => $config['api'].'|'.$config['secret']
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
        // Prepare
        $promise = new Promise();
        // Get data
        $data = $this->facebook->get($endpoint.'?'.http_build_query($query));
        // Resolve promise
        $promise->resolve($data);
        return $promise;
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
        return $this->_createRequest($endpoint, $query)->then(function($data) {
            // Parse posts
            $data = $data->getGraphEdge();
            $elements = $this->_parsePosts($data);
            // Get remaining data
            while(
                (empty($this->config['limit']) || count($elements) < $this->config['limit']) &&
                $data = $this->facebook->next($data)
            ) {
                $elements = array_merge($elements, $this->_parsePosts($data));
            }
            return $elements;
        });
    }
    
    /*
        Get a page's name
        
        Parameters
            string $id
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getPageName($id) {
        return $this->_createRequest("/$id")->then(function($data) {
            return $data->getGraphPage()['name'];
        });
    }
    
}