<?php

namespace Streams\Facebook;

use Streams\Facebook;
use Streams\Exception;
use GuzzleHttp;
use Goutte;
use Symfony\Component\DomCrawler\Crawler;

/*
    Facebook notes stream
*/
class Notes extends Facebook {
    
    /*
        GuzzleHttp\Client $guzzle
        array $elements
        string $author
    */
    protected $guzzle;
    protected $elements = [];
    protected $author;
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        // Init
        $this->guzzle = new GuzzleHttp\Client(['verify' => false]);
        // Get notes
        return $this->guzzle->getAsync("https://facebook.com/$this->id/notes")->then(function($response) {
            $crawler = new Crawler((string)$response->getBody());
            $requests = $crawler->filter('._52c6')->each(function($node) {
                return function() use($node) {
                    return $this->guzzle->getAsync('https://facebook.com'.$node->attr('href'))->then(function($response) {
                        $this->elements[$this->_getNewId()] = $this->_parsePost((string)$response->getBody());
                    });
                };
            });
            // Run requests
            $pool = new GuzzleHttp\Pool($this->guzzle, $requests);
            $pool->promise()->wait();
            return $this->_filterTypes($this->elements);
        });
    }
    
    /*
        Parse posts and create elements
        
        Parameters
            array $posts
        
        Return
            array
    */
    protected function _parsePost($body) {
        // Prepare
        $crawler = new Crawler($body);
        // Get page name
        if(!isset($this->author)) {
            $this->_getPageName($this->id)->then(function($name) use(&$author) {
                $this->author = $name;
            })->wait();
        }
        // Extract title
        $title = '';
        $nodes = $crawler->filter('._4lmk');
        if(count($nodes)) {
            $title = $nodes->first()->text();
        }
        // Extract text
        $text = '';
        $crawler->filter('._3dgx')->each(function($node) use(&$text) {
            $text .= $node->text();
        });
        $text = trim($text);
        // Return element
        return [
            'type' => 'text',
            'date' => null,
            'permalink' => 'https://facebook.com'.$crawler->filter('._39g5')->first()->attr('href'),
            'title' => $title,
            'description' => $text,
            'author' => $this->author,
            'avatar' => "http://graph.facebook.com/$this->id/picture?type=large"
        ];
    }
    
}