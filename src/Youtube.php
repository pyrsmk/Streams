<?php

namespace Streams;

use GuzzleHttp;

/*
    Base Youtube stream class
    
    API
        https://developers.google.com/youtube/v3/docs/
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
        Return the allowed max results per page
        
        Return
            integer
    */
    protected function _getMaxResultsPerPage() {
        return 50;
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
        Pagination
        
        Parameters
            string $endpoint
            array $query
            string $avatar
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _paginate($endpoint, array $query = [], $avatar = null) {
        return $this->_createRequest($endpoint, $query)->then(function($data) use($endpoint, $query, $avatar) {
            // Parse posts
            $elements = $this->_parsePosts($data['items'], $avatar);
            // Get remaining data
            $getNextPage = function($data) use($endpoint, $query, &$getNextPage, &$elements, $avatar) {
                if(empty($this->config['limit']) || count($elements) < $this->config['limit']) {
                    if(isset($data['nextPageToken'])) {
                        $query['pageToken'] = $data['nextPageToken'];
                        $this->_createRequest($endpoint, $query)->then(function($data) use(&$getNextPage, &$elements, $avatar) {
                            $elements = array_merge($elements, $this->_parsePosts($data['items'], $avatar));
                            $getNextPage($data);
                        })->wait();
                    }
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
            $video_id = isset($post['id']['videoId']) ? $post['id']['videoId'] : $post['snippet']['resourceId']['videoId'];
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
                'permalink' => 'https://www.youtube.com/watch?v='.$video_id,
                'preview' => [
                    'source' => $post['snippet']['thumbnails'][$quality]['url'],
                    'width' => 480,
                    'height' => 360
                ],
                'author' => $post['snippet']['channelTitle'],
                'description' => $post['snippet']['description']
            ];
            // Get avatar
            if($avatar === null) {
                $requests[] = function() use($post, &$elements, $id) {
                    return $this->_getAvatar($post['snippet']['channelId'])->then(function($url) use(&$elements, $id) {
                        $elements[$id]['avatar'] = $url;
                    });
                };
            }
            else {
                $elements[$id]['avatar'] = $avatar;
            }
            // Get embed code
            $requests[] = function() use($video_id, &$elements, $id) {                
                return $this->_getVideo($video_id)->then(function($video) use(&$elements, $id) {
                    $elements[$id]['html'] = $video['html'];
                    $elements[$id]['width'] = (int)$video['width'];
                    $elements[$id]['height'] = (int)$video['height'];
                }, function() use(&$elements, $id) {
                    unset($elements[$id]);
                });
            };
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $this->_filter($elements);
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