<?php

namespace Streams\Facebook;

use Streams\Facebook;
use Streams\Exception;

/*
    Facebook videos stream
*/
class Videos extends Facebook {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Prepare
        $query = [];
        $query['fields'] = 'created_time,title,description,embed_html,permalink_url,format';
        // Get videos
        return $this->_paginate("/$this->id/videos", $query);
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
        // Get page name
        $author = null;
        $this->_getPageName($this->id)->then(function($name) use(&$author) {
            $author = $name;
        })->wait();
        // Browse posts
        foreach($posts as $post) {
            // Prepare
            $id = $this->_getNewId();
            // Find the wider registered image
            $w = 0;
            $index = 0;
            foreach($post['format'] as $i => $image) {
                if($image['width'] > $w) {
                    $w = $image['width'];
                    $index = $i;
                }
            }
            // Add video
            preg_match('/width="(\d+?)".+?height="(\d+?)"/', $post['embed_html'], $matches);
            $elements[$id] = [
                'type' => 'embed',
                'date' => $post['created_time']->getTimestamp(),
                'html' => $post['embed_html'],
                'permalink' => 'https://facebook.com'.$post['permalink_url'],
                'title' => isset($post['title']) ? $post['title'] : null,
                'description' => isset($post['description']) ? $post['description'] : null,
                'preview' => [
                    'source' => $post['format'][$index]['picture'],
                    'width' => (int)$matches[1],
                    'height' => (int)$matches[2]
                ],
                'width' => (int)$matches[1],
                'height' => (int)$matches[2],
                'author' => $author,
                'avatar' => "http://graph.facebook.com/$this->id/picture?type=large"
            ];
        }
        return $this->_filterTypes($elements);
    }
    
}