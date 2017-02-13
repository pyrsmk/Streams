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
        $this->config['limit'] = isset($this->config['limit']) ? $this->config['limit'] : null;
        $this->config['mimetype'] = isset($this->config['mimetype']) ? $this->config['mimetype'] : false;
        // Init per_page var
        $max_results = $this->_getMaxResultsPerPage();
        if($max_results !== null) {
            if($this->config['limit'] === null || $this->config['limit'] > $max_results) {
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
    public function getElements() {
        return $this->_getElements()->then(function($elements) {
            // Verify element consistency
            foreach($elements as $id => $element) {
                if(!is_string($id) || strlen($id) != 32) {
                    throw new Exception("'$id' id is invalid");
                }
                if(!isset($element['type'])) {
                    throw new Exception("'type' field is required");
                }
                switch($element['type']) {
                    case 'text':
                        $fields = $this->_getTextTypeFields($element);
                        break;
                    case 'image':
                        $fields = $this->_getImageTypeFields($element);
                        break;
                    case 'video':
                        $fields = $this->_getVideoTypeFields($element);
                        break;
                    case 'embed':
                        $fields = $this->_getEmbedTypeFields($element);
                        break;
                    default:
                        throw new Exception("Unsupported '$type' element type");
                }
                foreach($fields as $field) {
                    if(!array_key_exists($field, $element)) {
                        throw new Exception("'$field' field not found");
                    }
                }
                foreach($element as $field => $value) {
                    if(!in_array($field, $fields)) {
                        throw new Exception("'$field' field not supported");
                    }
                }
            }
            // Limit elements
            if($this->config['limit'] !== null && count($elements) > $this->config['limit']) {
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
    protected function _filter($elements) {
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
                CURLOPT_TIMEOUT => 1
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
    
    /*
        Get the dimensions of a remote image
        
        Parameters
            string $url
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getImageSize($url) {
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
                CURLOPT_HTTPHEADER => ['Range: bytes=0-32768']
            ]
        ]);
        // Process data
        return $promise->then(function($response) {
            $data = $response->getBody();
            $image = @imagecreatefromstring($data);
            // GIF, JPEG
            if($image !== false) {
                $width = imagesx($image);
                $height = imagesy($image);
            }
            // PNG
            else {
                // https://github.com/tommoor/fastimage/blob/master/Fastimage.php
                list(, $width, $height) = unpack('N*', substr($data, 16, 8));
            }
            return [
                'width' => $width,
                'height' => $height
            ];
        });
    }
    
    /*
        Return text type fields
        
        Return
            array
    */
    protected function _getTextTypeFields($element) {
        return ['type', 'date', 'author', 'avatar', 'title', 'description', 'permalink'];
    }
    
    /*
        Return image type fields
        
        Return
            array
    */
    protected function _getImageTypeFields($element) {
        return ['type', 'date', 'author', 'avatar', 'title', 'description', 'permalink', 'source', 'width', 'height', 'mimetype'];
    }
    
    /*
        Return video type fields
        
        Return
            array
    */
    protected function _getVideoTypeFields($element) {
        return ['type', 'date', 'author', 'avatar', 'title', 'description', 'permalink', 'source', 'width', 'height', 'mimetype', 'preview'];
    }
    
    /*
        Return embed type fields
        
        Return
            array
    */
    protected function _getEmbedTypeFields($element) {
        return ['type', 'date', 'author', 'avatar', 'title', 'description', 'permalink', 'html', 'width', 'height', 'preview'];
    }
    
}