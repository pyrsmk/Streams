<?php

namespace Streams;

use GuzzleHttp;

/*
    Base Flickr stream class
*/
abstract class Flickr extends AbstractStream {
    
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
        // Init Guzzle
        $this->guzzle = new GuzzleHttp\Client(['verify' => false]);
        // Normalize
        $config['description'] = isset($config['description']) ? $config['description'] : false;
        $config['mimetype'] = isset($config['mimetype']) ? $config['mimetype'] : false;
        // Construct parent
        parent::__construct($id, $config);
    }
    
    /*
        Create a request
        
        Parameters
            array $query
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _createRequest(array $query) {
        // Define request
        $promise = $this->guzzle->getAsync('https://api.flickr.com/services/rest/', [
            'query' => array_merge($query, [
                'api_key' => $this->config['api'],
                'format' => 'json',
                'nojsoncallback' => 1
            ])
        ]);
        // Process data
        return $promise->then(function($response) {
            $data = json_decode($response->getBody(), true);
            if($data['stat'] == 'fail') {
                throw new Exception($data['message']);
            }
            return $data;
        });
    }
    
    /*
        Get NSID from a username
        
        Parameters
            string $name
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getNSID($name) {
        return $this->_createRequest([
            'method' => 'flickr.urls.lookupUser',
            'url' => 'https://www.flickr.com/photos/'.$name
        ])->then(function($data) {
            return $data['user']['id'];
        });
    }
    
    /*
        Get a photo description
        
        Parameters
            string $id
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getDescription($id) {
        return $this->_createRequest([
            'method' => 'flickr.photos.getInfo',
            'photo_id' => $id
        ])->then(function($data) {
            return $data['photo']['description']['_content'];
        });
    }
    
}