<?php

require('_base.php');

runStream(function() {
    return new Streams\Dribbble\Project('280804-Graphics', [
        'token' => '6001fa7b14dcb624bcc284709c6bde0aef723a9952b9e94da058f56f1f56a37b',
        'limit' => 100
    ]);
});