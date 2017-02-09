<?php

namespace Streams;

use GuzzleHttp;

/*
    Base Youtube stream class
*/
abstract class Youtube extends AbstractStream {
    
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
        $promise = $this->guzzle->getAsync("https://www.googleapis.com/youtube/v3$endpoint", [
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
    
    /*
        Get avatar from a channel id
        
        Parameters
            string $id
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getAvatar($id) {
        return $this->_createRequest('/channels', [
            'id' => $id,
            'part' => 'snippet'
        ])->then(function($data) {
            $qualities = ['maxres', 'standard', 'high', 'medium', 'default'];
            foreach($qualities as $quality) {
                if(isset($data['items'][0]['snippet']['thumbnails'][$quality])) {
                    break;
                }
            }
            return $data['items'][0]['snippet']['thumbnails'][$quality]['url'];
        });
    }
    
    /*
        Get video
        
        Parameters
            string $id
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getVideo($id) {
        return $this->_createRequest('/videos', [
            'id' => $id,
            'part' => 'player'
        ])->then(function($data) use(&$elements, $id) {
            preg_match('/width="(\d+?)".+?height="(\d+?)"/', $data['items'][0]['player']['embedHtml'], $matches);
            return [
                'html' => $data['items'][0]['player']['embedHtml'],
                'width' => $matches[1],
                'height' => $matches[2]
            ];
        });
    }
    
}