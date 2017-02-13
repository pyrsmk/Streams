<?php

require('_base.php');

runStream(function() {
    return new Streams\Vimeo\Group('animation', [
        'api' => '0a35200c250631d1797c6c7dcf1d91b76af0a81c',
        'secret' => 'GPaIc3bVs/saZb9Y2zT6Vw2JE8klpbYdWGly4Rqggod0YJYh5spY5EKUJiPci3kt7w67GSgOM1J5j+/5sr80lcBtySF2+U/IAUbR5dhrhN4Jx/5x42jCZC7ivj3+nOIP',
        'nsfw' => false,
        'limit' => 100
    ]);
});