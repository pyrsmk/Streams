<?php

require('_base.php');

runStream(function() {
    return new Streams\FileSystem\Directory('./medias/');
});