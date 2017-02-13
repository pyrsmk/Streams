<?php

namespace Streams\Dribbble;

use Streams\Dribbble;

/*
    Dribbble team stream
*/
class Team extends Dribbble {
    
    /*
        Get elements
        
        Return
            GuzzleHttp\Promise\Promise
    */
    protected function _getElements() {
        return $this->_paginate("/teams/$this->id/shots");
    }
    
}