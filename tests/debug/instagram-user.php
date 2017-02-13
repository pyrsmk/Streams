<?php

require('_base.php');

runStream(function() {
    return new Streams\Instagram\User('lindzeepoi');
});