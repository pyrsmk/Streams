<?php

namespace Streams\Youtube;

use Streams\Youtube;
use Streams\Exception;
use GuzzleHttp;

/*
    Youtube channel stream
*/
class Channel extends Youtube {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Prepare
        $nsfw = $this->config['nsfw'] ? 'none' : 'strict';
        $avatar = null;
        $this->_getAvatar($this->id)->then(function($url) use(&$avatar) {
            $avatar = $url;
        })->wait();
        // Load the first page
        return $this->_createRequest('/search', [
            'maxResults' => $this->per_page,
            'channelId' => $this->id,
            'part' => 'snippet',
            'type' => 'video',
            'order' => 'date',
            'safeSearch' => $nsfw
        ])->then(function($data) use($maxResults, $nsfw, $avatar) {
            // Parse posts
            $elements = $this->_parsePosts($data['items'], $avatar);
            // Get remaining data
            $getNextPage = function($data) use(&$getNextPage, &$elements, $maxResults, $nsfw, $avatar) {
                if(isset($data['nextPageToken'])) {
                    $this->_createRequest('/search', [
                        'maxResults' => $maxResults,
                        'channelId' => $this->id,
                        'part' => 'snippet',
                        'type' => 'video',
                        'order' => 'date',
                        'safeSearch' => $nsfw,
                        'pageToken' => $data['nextPageToken']
                    ])->then(function($data) use(&$getNextPage, &$elements, $avatar) {
                        $elements = array_merge($elements, $this->_parsePosts($data['items'], $avatar));
                        if($this->config['limit'] === null || count($elements) < $this->config['limit']) {
                            $getNextPage($data);
                        }
                    })->wait();
                }
            };
            $getNextPage($data);
            return $elements;
        });
    }
    
    /*
        Parse posts and create elements
        
        Parameters
            array $posts
            string $avatar
        
        Return
            array
    */
    protected function _parsePosts($posts, $avatar) {
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
                'permalink' => 'https://www.youtube.com/watch?v='.$post['id']['videoId'],
                'preview' => $post['snippet']['thumbnails'][$quality]['url'],
                'author' => $post['snippet']['channelTitle'],
                'avatar' => $avatar,
                'description' => $post['snippet']['description']
            ];
            // Get embed code
            $requests[] = function() use($post, &$elements, $id) {                
                return $this->_getVideo($post['id']['videoId'])->then(function($video) use(&$elements, $id) {
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