<?php

namespace Streams\Facebook;

use Streams\Facebook;
use Streams\Exception;

/*
    Facebook videos stream
*/
class Videos extends Facebook {
    
    /*
        string $name
    */
    protected $name;
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Prepare
        $query = [];
        $query['fields'] = 'created_time,title,description,embed_html,permalink_url,format';
        // Get page name
        $this->_createRequest("/$this->id")->then(function($data) {
            $this->name = $data->getGraphPage()['name'];
        });
        // Load the first page
        return $this->_createRequest("/$this->id/videos", $query)->then(function($data) {
            // Parse posts
            $data = $data->getGraphEdge();
            $elements = $this->_parsePosts($data);
            // Get remaining data
            while(
                ($this->config['limit'] === null || count($elements) < $this->config['limit']) &&
                $data = $this->facebook->next($data)
            ) {
                $elements = array_merge($elements, $this->_parsePosts($data));
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
                'preview' => $post['format'][$index]['picture'],
                'width' => $matches[1],
                'height' => $matches[2],
                'author' => $this->name,
                'avatar' => "http://graph.facebook.com/$this->id/picture?type=large"
            ];
        }
        return $elements;
    }
    
}