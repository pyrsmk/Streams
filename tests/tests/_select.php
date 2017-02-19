<?php

$suite = new MiniSuite\Suite('Select element types');

$suite->expects("text")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\GooglePlus\People('+frandroid', [
                'api' => 'AIzaSyAlKfOvbX_fJG7fNR7_H3N5HW8teFI_GO0',
                'limit' => 10,
                'select' => ['text']
            ]);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            foreach($elements as $element) {
                if($element['type'] != 'text') {
                    throw new Exception("'{$element['type']}' element type encountered, 'text' expected");
                }
            }
            return count($elements);
      })
      ->equals(10);

$suite->expects("image")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\GooglePlus\People('+frandroid', [
                'api' => 'AIzaSyAlKfOvbX_fJG7fNR7_H3N5HW8teFI_GO0',
                'limit' => 10,
                'select' => ['image']
            ]);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            foreach($elements as $element) {
                if($element['type'] != 'image') {
                    throw new Exception("'{$element['type']}' element type encountered, 'image' expected");
                }
            }
            return count($elements);
      })
      ->equals(10);

$suite->expects("video")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\FileSystem\Directory('medias/', [
                'select' => ['video']
            ]);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            foreach($elements as $element) {
                if($element['type'] != 'video') {
                    throw new Exception("'{$element['type']}' element type encountered, 'video' expected");
                }
            }
            return count($elements);
      })
      ->equals(1);

$suite->expects("embed")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\GooglePlus\People('+frandroid', [
                'api' => 'AIzaSyAlKfOvbX_fJG7fNR7_H3N5HW8teFI_GO0',
                'limit' => 10,
                'select' => ['embed']
            ]);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            foreach($elements as $element) {
                if($element['type'] != 'embed') {
                    throw new Exception("'{$element['type']}' element type encountered, 'embed' expected");
                }
            }
            return count($elements);
      })
      ->equals(10);