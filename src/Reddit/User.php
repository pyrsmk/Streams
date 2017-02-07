<?php

namespace Streams\Reddit;

use Streams\Reddit;
use Streams\Exception;
use GuzzleHttp;

/*
    Reddit user stream
*/
class User extends Reddit {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_createRequest("/user/$this->id/submitted")->then(function($data) {
            // Parse posts
            $elements = $this->_parsePosts($data['data']['children']);
            // Get remaining data
            $getNextPage = function($data) use(&$getNextPage, &$elements) {
                if(isset($data['data']['after'])) {
                    $this->_createRequest("/user/$this->id/submitted", [
                        'after' => $data['data']['after']
                    ])->then(function($data) use(&$getNextPage, &$elements) {
                        $elements = array_merge($elements, $this->_parsePosts($data['data']['children']));
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
            // NSFW verification
            if(!$this->config['nsfw'] && $post['data']['over_18']) {
                return;
            }
            // Prepare
            $id = $this->_getNewId();
            // Base
            $elements[$id] = [
                'date' => $post['data']['created'],
                'permalink' => "https://reddit.com{$post['data']['permalink']}",
                'title' => $post['data']['title'],
                'description' => null,
                'author' => $post['data']['author'],
                'avatar' => null
            ];
            // Text
            if(!isset($post['data']['preview'])) {
                $elements[$id]['type'] = 'text';
                $elements[$id]['description'] = $post['data']['selftext_html'];
            }
            // Image
            else if($post['data']['media'] === null) {
                $elements[$id]['type'] = 'image';
                $elements[$id]['source'] = str_replace('&amp;', '&', $post['data']['preview']['images'][0]['source']['url']);
                $elements[$id]['width'] = $post['data']['preview']['images'][0]['source']['width'];
                $elements[$id]['height'] = $post['data']['preview']['images'][0]['source']['height'];
                // Get mime type
                $requests[] = function() use(&$elements, $id) {
                    return $this->_getMimetype($elements[$id]['source'])->then(function($mimetype) use(&$elements, $id) {
                        $elements[$id]['mimetype'] = $mimetype;
                    }, function() use(&$elements, $id) {
                        unset($elements[$id]);
                    });
                };
            }
            // Embed
            else {
                $elements[$id]['type'] = 'embed';
                $elements[$id]['html'] = htmlspecialchars_decode($post['data']['media']['oembed']['html']);
                $elements[$id]['width'] = $post['data']['media']['oembed']['width'];
                $elements[$id]['height'] = $post['data']['media']['oembed']['height'];
                $elements[$id]['preview'] = $post['data']['media']['oembed']['thumbnail_url'];
            }
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $elements;
    }
    
}