<?php

namespace Streams;

use GuzzleHttp;

/*
    Base Flickr stream class
    
    API
        https://www.flickr.com/services/api/
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
        return 500;
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
        Pagination
        
        Parameters
            array $query
            string $datagroup
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _paginate(array $query = [], $datagroup) {
        return $this->_createRequest($query)->then(function($data) use($query, $datagroup) {
            // Parse posts
            $ownername = isset($data[$datagroup]['ownername']) ? $data[$datagroup]['ownername'] : $data[$datagroup]['photo'][0]['ownername'];
            $elements = $this->_parsePosts($data[$datagroup]['photo'], $ownername, $query['user_id']);
            // Load remaining data
            if(empty($this->config['limit']) || count($elements) < $this->config['limit']) {
                // Prepare
                $requests = [];
                $start = count($elements) + 1;
                if(empty($this->config['limit'])) {
                    $end = $data[$datagroup]['total'];
                }
                else {
                    $end = $this->config['limit'] < $data[$datagroup]['total'] ? $this->config['limit'] : $data[$datagroup]['total'];
                }
                $end = ceil($end/$this->per_page);
                // Create further requests
                for($page=2, $j=$end; $page<$j; ++$page) {
                    $requests[] = function() use($query, $datagroup, &$elements, $page) {
                        // Set page
                        $query['page'] = $page;
                        // Add new request
                        return $this->_createRequest($query)->then(function($data) use($query, $datagroup, &$elements) {
                            $ownername = isset($data[$datagroup]['ownername']) ? $data[$datagroup]['ownername'] : $data[$datagroup]['photo'][0]['ownername'];
                            $posts = $this->_parsePosts($data[$datagroup]['photo'], $ownername, $query['user_id']);
                            foreach($posts as $id => $element) {
                                $elements[$id] = $element;
                            }
                        });
                    };
                }
                // Run all requests
                $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
                $pool->promise()->wait();
            }
            return $elements;
        });
    }
    
    /*
        Parse posts and create elements
        
        Parameters
            array $posts
            string $ownername
            string $user_id
        
        Return
            array
    */
    protected function _parsePosts($posts, $ownername, $user_id) {
        // Prepare
        $elements = [];
        $requests = [];
        // Browse posts
        foreach($posts as $post) {
            // Prepare
            $id = $this->_getNewId();
            // Base
            $elements[$id] = [
                'date' => (int)$post['dateupload'],
                'permalink' => "https://www.flickr.com/photos/$user_id/{$post['id']}/in/dateposted/",
                'title' => $post['title'],
                'author' => $ownername,
                'avatar' => $post['iconserver'] > 0 ?
                            "http://farm{$post['iconfarm']}.staticflickr.com/{$post['iconserver']}/buddyicons/$user_id.jpg" :
                            "https://www.flickr.com/images/buddyicon.gif"
            ];
            // Add description
            $requests[] = function() use($post, &$elements, $id) {
                return $this->_getDescription($post['id'])->then(function($description) use(&$elements, $id) {
                    $elements[$id]['description'] = $description;
                });
            };
            // Image
            if($post['media'] == 'photo' && isset($post['url_l'])) {
                $requests[] = function() use($post, &$elements, $id) {
                    return $this->_getMimetype($post['url_l'])->then(function($mimetype) use(&$elements, $id) {
                        $elements[$id]['mimetype'] = $mimetype;
                    }, function() use(&$elements, $id) {
                        unset($elements[$id]);
                    });
                };
                $elements[$id]['type'] = 'image';
                $elements[$id]['source'] = $post['url_l'];
                $elements[$id]['width'] = (int)$post['width_l'];
                $elements[$id]['height'] = (int)$post['height_l'];
            }
            // Embed
            else if($post['media'] == 'video' && isset($post['url_l'])) {
                $elements[$id]['type'] = 'embed';
                $elements[$id]['html'] =
                    "<object type=\"application/x-shockwave-flash\" width=\"{$post['width_l']}\" height=\"{$post['height_l']}\" data=\"https://www.flickr.com/apps/video/stewart.swf?photo_id={$post['id']}&photo_secret={$post['secret']}\"  classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"><param name=\"flashvars\" value=\"flickr_show_info_box=true\"></param><param name=\"movie\" value=\"https://www.flickr.com/apps/video/stewart.swf?photo_id={$post['id']}&photo_secret={$post['secret']}\"></param><param name=\"bgcolor\" value=\"#000000\"></param><param name=\"allowFullScreen\" value=\"true\"></param><embed type=\"application/x-shockwave-flash\" src=\"https://www.flickr.com/apps/video/stewart.swf?photo_id={$post['id']}&photo_secret={$post['secret']}\" bgcolor=\"#000000\" allowfullscreen=\"true\" flashvars=\"flickr_show_info_box=true\" width=\"{$post['width_l']}\" height=\"{$post['height_l']}\"></embed></object>";
                $elements[$id]['preview'] = [
                    'source' => $post['url_l'],
                    'width' => (int)$post['width_l'],
                    'height' => (int)$post['height_l']
                ];
                $elements[$id]['width'] = (int)$post['width_l'];
                $elements[$id]['height'] = (int)$post['height_l'];
            }
            else {
                array_pop($requests);
                unset($elements[$id]);
            }
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $this->_filterTypes($elements);
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