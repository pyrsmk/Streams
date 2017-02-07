<?php

namespace Streams\Flickr;

use Streams\Flickr;
use Streams\Exception;
use GuzzleHttp;

/*
    Flickr user stream
*/
class User extends Flickr {
    
    /*
        string $user_id
    */
    protected $user_id;
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Get user id
        $this->_getNSID($this->id)->then(function($nsid) {
            $this->user_id = $nsid;
        })->wait();
        // Compute how many posts per page we need
        if($this->config['limit'] === null || $this->config['limit'] > 500) {
            $per_page = 500;
        }
        else {
            $per_page = $this->config['limit'];
        }
        // Load the first page
        return $this->_createRequest([
            'method' => 'flickr.people.getPublicPhotos',
            'user_id' => $this->user_id,
            'extras' => 'date_upload,url_l,media,owner_name,icon_server',
            'per_page' => $per_page
        ])->then(function($data) use($per_page) {
            // Parse posts
            $elements = $this->_parsePosts($data['photos']['photo']);
            // Prepare
            $requests = [];
            if($this->config['limit'] === null) {
                $remaining = $data['photos']['total'] - count($data['photos']['photo']);
            }
            else {
                $remaining = $this->config['limit'] - count($data['photos']['photo']);
            }
            // Get remaining data
            for($i=2, $j=ceil($remaining/$per_page)+1; $i<$j+1; ++$i) {
                $requests[] = function() use(&$elements, $per_page, $i) {
                    return $this->_createRequest([
                        'method' => 'flickr.people.getPublicPhotos',
                        'user_id' => $this->user_id,
                        'extras' => 'date_upload,url_l,media,owner_name,icon_server',
                        'per_page' => $per_page,
                        'page' => $i
                    ])->then(function($data) use(&$elements) {
                        $posts = $this->_parsePosts($data['photos']['photo']);
                        foreach($posts as $id => $element) {
                            $elements[$id] = $element;
                        }
                    });
                };
            }
            $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
            $pool->promise()->wait();
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
                'date' => $post['dateupload'],
                'permalink' => "https://www.flickr.com/photos/$this->user_id/{$post['id']}/in/dateposted/",
                'title' => $post['title'],
                'width' => $post['width_l'],
                'height' => $post['height_l'],
                'author' => $post['ownername'],
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
    
}