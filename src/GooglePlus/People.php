<?php

namespace Streams\GooglePlus;

use Streams\GooglePlus;
use GuzzleHttp;

/*
    Youtube channel stream
*/
class People extends GooglePlus {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Compute how many posts per page we need
        if($this->config['limit'] === null || $this->config['limit'] > 100) {
            $maxResults = 100;
        }
        else {
            $maxResults = $this->config['limit'];
        }
        // Load the first page
        return $this->_createRequest(
            "/people/$this->id/activities/public",
            ['maxResults' => $maxResults]
        )->then(function($data) use($maxResults) {
            // Parse posts
            $elements = $this->_parsePosts($data['items']);
            // Get remaining data
            $getNextPage = function($data) use(&$getNextPage, &$elements, $maxResults) {
                if(isset($data['nextPageToken'])) {
                    $this->_createRequest("/people/$this->id/activities/public", [
                        'maxResults' => $maxResults,
                        'pageToken' => $data['nextPageToken']
                    ])->then(function($data) use(&$getNextPage, &$elements) {
                        $elements = array_merge($elements, $this->_parsePosts($data['items']));
                        if($this->config['limit'] === null || count($elements) < $this->config['limit']) {
                            $getNextPage($data);
                        }
                    })->wait();
                }
            };
            $getNextPage($data);
            // Limit elements
            if($this->config['limit'] !== null && count($elements) > $this->config['limit']) {
                $elements = array_slice($elements, 0, $this->config['limit']);
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
            // Base
            $elements[$id] = [
                'date' => strtotime($post['published']),
                'permalink' => $post['url'],
                'author' => $post['actor']['displayName'],
                'avatar' => $post['actor']['image']['url'],
                'title' => $post['title'],
                'description' => $post['object']['content']
            ];
            // Text
            if(!isset($post['object']['attachments'][0]['objectType']) || $post['object']['attachments'][0]['objectType'] == 'article') {
                $elements[$id]['type'] = 'text';
            }
            // Image
            else if($post['object']['attachments'][0]['objectType'] == 'photo') {
                $elements[$id]['type'] = 'image';
                $elements[$id]['source'] = $post['object']['attachments'][0]['fullImage']['url'];
                $requests[] = function() use($post, &$elements, $id) {
                    $url = $post['object']['attachments'][0]['fullImage']['url'];
                    return $this->_getMimetype($url)->then(function($mimetype) use(&$elements, $id) {
                        $elements[$id]['mimetype'] = $mimetype;
                    });
                };
                $requests[] = function() use($post, &$elements, $id) {
                    return $this->_getImageSize($elements[$id]['source'])->then(function($size) use(&$elements, $id) {
                        $elements[$id]['width'] = $size['width'];
                        $elements[$id]['height'] = $size['height'];
                    });
                };
            }
            // Embed
            else if(isset($post['object']['attachments'][0]['embed'])) {
                $elements[$id]['type'] = 'embed';
                $elements[$id]['html'] = "<iframe src=\"{$post['object']['attachments'][0]['embed']['url']}\" width=\"{$post['object']['attachments'][0]['image']['width']}\" height=\"{$post['object']['attachments'][0]['image']['height']}\"></iframe>";
                $elements[$id]['width'] = $post['object']['attachments'][0]['image']['width'];
                $elements[$id]['height'] = $post['object']['attachments'][0]['image']['height'];
                $elements[$id]['preview'] = $post['object']['attachments'][0]['image']['url'];
            }
            // Unsupported/invalid
            else {
                unset($elements[$id]);
            }
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $elements;
    }
    
}