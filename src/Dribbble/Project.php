<?php

namespace Streams\Dribbble;

use Streams\Dribbble;

/*
    Dribbble project stream
*/
class Project extends Dribbble {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/projects/$this->id/shots");
    }
    
}