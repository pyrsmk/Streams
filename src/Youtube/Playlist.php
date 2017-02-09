<?php

namespace Streams\Youtube;

use Streams\Youtube;
use Streams\Exception;
use GuzzleHttp;

/*
    Youtube playlist stream
*/
class Playlist extends Youtube {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Prepare
        $nsfw = $this->config['nsfw'] ? 'none' : 'strict';
        if($this->config['limit'] === null || $this->config['limit'] > 50) {
            $maxResults = 50;
        }
        else {
            $maxResults = $this->config['limit'];
        }
        // Load the first page
        return $this->_createRequest('/playlistItems', [
            'maxResults' => $maxResults,
            'playlistId' => $this->id,
            'part' => 'snippet'
        ])->then(function($data) use($maxResults, $nsfw) {
            // Parse posts
            $elements = $this->_parsePosts($data['items']);
            // Get remaining data
            $getNextPage = function($data) use(&$getNextPage, &$elements, $maxResults, $nsfw) {
                if(isset($data['nextPageToken'])) {
                    $this->_createRequest('/playlistItems', [
                        'maxResults' => $maxResults,
                        'playlistId' => $this->id,
                        'part' => 'snippet',
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
        $qualities = ['maxres', 'standard', 'high', 'medium', 'default'];
        // Browse posts
        foreach($posts as $post) {
            // Prepare
            $id = $this->_getNewId();
            // Pick the right thumbnail
            foreach($qualities as $quality) {
                if(isset($post['snippet']['thumbnails'][$quality])) {
                    break;
                }
            }
            // Save element
            $elements[$id] = [
                'type' => 'embed',
                'title' => $post['snippet']['title'],
                'date' => strtotime($post['snippet']['publishedAt']),
                'permalink' => 'https://www.youtube.com/watch?v='.$post['snippet']['resourceId']['videoId'],
                'preview' => $post['snippet']['thumbnails'][$quality]['url'],
                'author' => $post['snippet']['channelTitle'],
                'description' => $post['snippet']['description']
            ];            
            // Get avatar
            $requests[] = function() use($post, &$elements, $id) {
                return $this->_getAvatar($post['snippet']['channelId'])->then(function($url) use(&$elements, $id) {
                    $elements[$id]['avatar'] = $url;
                });
            };
            // Get embed code
            $requests[] = function() use($post, &$elements, $id) {
                return $this->_getVideo($post['snippet']['resourceId']['videoId'])->then(function($video) use(&$elements, $id) {
                    $elements[$id]['html'] = $video['html'];
                    $elements[$id]['width'] = $video['width'];
                    $elements[$id]['height'] = $video['height'];
                }, function() use($post, &$elements, $id) {
                    unset($elements[$id]);
                });
            };
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $elements;
    }
    
}