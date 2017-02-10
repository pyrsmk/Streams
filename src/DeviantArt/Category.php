<?php

namespace Streams\DeviantArt;

use Streams\DeviantArt;
use Streams\Exception;
use GuzzleHttp;

/*
    DeviantArt category stream
*/
class Category extends DeviantArt {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Prepare request
        $this->config['type'] = isset($this->config['type']) ? $this->config['type'] : 'popular8h';
        switch($this->config['type']) {
            case 'newest':
                $endpoint = '/browse/newest';
                break;
            case 'hot':
                $endpoint = '/browse/hot';
                break;
            case 'undiscovered':
                $endpoint = '/browse/undiscovered';
                break;
            case 'popular':
                $endpoint = '/browse/popular';
                $query = ['timerange' => 'alltime'];
                break;
            case 'popular8h':
                $endpoint = '/browse/popular';
                $query = ['timerange' => '8hr'];
                break;
            case 'popular24h':
                $endpoint = '/browse/popular';
                $query = ['timerange' => '24hr'];
                break;
            case 'popular3d':
                $endpoint = '/browse/popular';
                $query = ['timerange' => '3days'];
                break;
            case 'popular1w':
                $endpoint = '/browse/popular';
                $query = ['timerange' => '1week'];
                break;
            case 'popular1m':
                $endpoint = '/browse/popular';
                $query = ['timerange' => '1month'];
                break;
        }
        $query['category_path'] = $this->id;
        $query['mature_content'] = $this->config['nsfw'];
        $query['limit'] = $this->per_page;
        // Get data
        return $this->_paginate($endpoint, $query);
    }
    
}