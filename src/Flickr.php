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
        if(!isset($config['api'])) {
            throw new Exception("'api' parameter must be defined");
        }
        $config['description'] = isset($config['description']) ? $config['description'] : false;
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
        Parse posts and create elements
        
        Parameters
            array $posts
            string $ownername
        
        Return
            array
    */
    protected function _parsePosts($posts, $ownername) {
        // Prepare
        $elements = [];
        $requests = [];
        // Browse posts
        foreach($posts as $post) {
            // Prepare
            $id = $this->_getNewId();
            // Base
            $elements[$id] = [
                'date' => $post['dateupload'],
                'permalink' => "https://www.flickr.com/photos/$this->user_id/{$post['id']}/in/dateposted/",
                'title' => $post['title'],
                'width' => $post['width_l'],
                'height' => $post['height_l'],
                'author' => $ownername,
                'avatar' => $post['iconserver'] > 0 ?
                            "http://farm{$post['iconfarm']}.staticflickr.com/{$post['iconserver']}/buddyicons/$this->user_id.jpg" :
                            "https://www.flickr.com/images/buddyicon.gif"
            ];
            // Add description
            $requests[] = function() use($post, &$elements, $id) {
                return $this->_getDescription($post['id'])->then(function($description) use(&$elements, $id) {
                    $elements[$id]['description'] = $description;
                });
            };
            // Image
            if($post['media'] == 'photo') {
                $requests[] = function() use($post, &$elements, $id) {
                    return $this->_getMimetype($post['url_l'])->then(function($mimetype) use(&$elements, $id) {
                        $elements[$id]['mimetype'] = $mimetype;
                    }, function() use(&$elements, $id) {
                        unset($elements[$id]);
                    });
                };
                $elements[$id]['type'] = 'image';
                $elements[$id]['source'] = $post['url_l'];
            }
            // Embed
            else if($post['media'] == 'video') {
                $elements[$id]['type'] = 'embed';
                $elements[$id]['html'] =
                    "<object type=\"application/x-shockwave-flash\" width=\"{$post['width_l']}\" height=\"{$post['height_l']}\" data=\"https://www.flickr.com/apps/video/stewart.swf?photo_id={$post['id']}&photo_secret={$post['secret']}\"  classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"><param name=\"flashvars\" value=\"flickr_show_info_box=true\"></param><param name=\"movie\" value=\"https://www.flickr.com/apps/video/stewart.swf?photo_id={$post['id']}&photo_secret={$post['secret']}\"></param><param name=\"bgcolor\" value=\"#000000\"></param><param name=\"allowFullScreen\" value=\"true\"></param><embed type=\"application/x-shockwave-flash\" src=\"https://www.flickr.com/apps/video/stewart.swf?photo_id={$post['id']}&photo_secret={$post['secret']}\" bgcolor=\"#000000\" allowfullscreen=\"true\" flashvars=\"flickr_show_info_box=true\" width=\"{$post['width_l']}\" height=\"{$post['height_l']}\"></embed></object>";
                $elements[$id]['preview'] = $post['url_l'];
            }
        }
        // Populate last fields
        $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
        $pool->promise()->wait();
        return $elements;
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