Streams 1.2.0
=============

Streams aims to unify several social feeds with the same API. It is heavily based on Guzzle and async requests.

This library is part of the Myriade 2 project (link coming soon).

It currently supports :

- DeviantArt
- Dribbble
- Facebook
- 500px
- Flickr
- Google Plus
- Instagram
- Reddit
- Vimeo
- Youtube

Install
-------

```
composer require pyrsmk/streams
```

A quick example
---------------

Let's see how we can get 50 photos from a National Geographic album on Facebook :

```php
$stream = new Streams\Facebook\Album('10150205173893951', [
    'api' => '*****',
    'secret' => '*****',
    'limit' => 50
]);

$stream->get()->then(function($elements) {
    // Print all retrieved images
    foreach($elements as $element) {
        echo "<img src=\"{$element['source']}\" alt=\"{$element['title']}\">";
    }
    // calling wait() is needed only if you want to wait
    // for the request to complete before continue the script
})->wait();
```

### Notes

The several supported APIs don't support pagination similarly. While pagination is completely transparent in Streams, if we don't set a limit in the stream parameters, the stream will try to get all the possible elements. Often, the stream is unable to get all existing elements, because the corresponding API forbids it (the pagination is incomplete). But some streams have an infinite pagination (they get all existing elements), and the request can take a really long time, even throw a memory exceeded exception from PHP (our tests showed us that PHP crashes around 10000 retrieved elements).

Types
-----

Each element can be of 4 different types : `text`, `image`, `video` and `embed`. Embed can be an embedded video, or any other HTML code that an API can return.

Here's the data returned by each type of element.

## Text

```php
[
    'type' => 'text',
    'date' => integer,
    'author' => string,
    'avatar' => string,
    'title' => string,
    'description' => string,
    'permalink' => string
]
```

## Image

```php
[
    'type' => 'image',
    'date' => integer,
    'author' => string,
    'avatar' => string,
    'title' => string,
    'description' => string,
    'permalink' => string,
    'source' => string,
    'width' => integer,
    'height' => integer,
    'mimetype' => string
]
```

## Video

```php
[
    'type' => 'video',
    'date' => integer,
    'author' => string,
    'avatar' => string,
    'title' => string,
    'description' => string,
    'permalink' => string,
    'source' => string,
    'width' => integer,
    'height' => integer,
    'mimetype' => string,
    'preview' => [
        'source' => string,
        'width' => integer,
        'height' => integer
    ]
]
```

## Embed

```php
[
    'type' => 'embed',
    'date' => integer,
    'author' => string,
    'avatar' => string,
    'title' => string,
    'description' => string,
    'permalink' => string,
    'html' => string,
    'width' => integer,
    'height' => integer,
    'preview' => [
        'source' => string,
        'width' => integer,
        'height' => integer
    ]
]
```

### Notes

- mime types are retrieved automatically by Streams; it creates many additional requests but since they're run all at once aynschronously, the footprint is trivial

Streams
-------

Here's the list of the available streams and their respective options.

## Options

Streams have some base options :

- `nsfw` : `true` to retrieve NSFW elements (default : `false`)
- `limit` : a number of elements to get (default : `false`)
- `select` : list the element types to get (default : `['text', 'image', 'video', 'embed']`)

Streams can have several additional options, according to the respective API. They often need an `api` and `secret` options fulfilled. These options are usually the `client_id` and `client_secret` after the creation of an application on the remote website.

## File system

```php
new Streams\FileSystem\Directory('some/path/', [
    'limit' => 50
]);
```

### Notes

- support image and video types
- do not support video previews
- do not support permalinks
- do not support NSFW
- do not support author and avatar
- do not support description

## DeviantArt

Get images from a category (the ID of a category is visible in the URI of the corresponding page on DeviantArt) :

```php
new Streams\DeviantArt\Category('photography/nature', [
    'api' => '*****',
    'secret' => '*****',
    'nsfw' => false,
    'limit' => 50,
    // either 'newest', 'hot', 'undiscovered', 'popular',
    // 'popular8h' (default), 'popular24h', 'popular3d', 'popular1w' or 'popular1m'
    'type' => 'newest'
]);
```

Get root images on a gallery of a user (other specific galleries are not supported) :

```php
new Streams\DeviantArt\User('numyumy', [
    'api' => '*****',
    'secret' => '*****',
    'nsfw' => false,
    'limit' => 50
]);
```

### Notes

The DeviantArt API is not really mature. We had pretty bad times with it, and as far as we can tell, there're many things that don't work because of design problems on the API (and some other weird things).

- support texts and images
- impossible to get the description for an image
- impossible to get videos
- impossible to get a specific gallery (https://stackoverflow.com/questions/28581350/obtain-deviantart-deviation-id-from-page-url)
- sometimes requests can be __really__ slow

## Dribbble

Get images from a bucket :

```php
new Streams\Dribbble\Bucket('476346-Usabilty-examples', [
    'token' => '*****',
    'limit' => 50
]);
```

Get images from a project :

```php
new Streams\Dribbble\Project('280804-Graphics', [
    'token' => '*****',
    'limit' => 50
]);
```

Get images from a team :

```php
new Streams\Dribbble\Team('Creativedash', [
    'token' => '*****',
    'limit' => 50
]);
```

Get images from a user :

```php
new Streams\Dribbble\User('BurntToast', [
    'token' => '*****',
    'limit' => 50
]);
```

### Notes

- support images
- do not support NSFW

## Facebook

Get images from an album on a page :

```php
new Streams\Facebook\Album('1710763805841434', [
    'api' => '*****',
    'secret' => '*****',
    'limit' => 50
]);
```

Get profile photos from a page :

```php
new Streams\Facebook\Album('ChatNoirDesign', [
    'api' => '*****',
    'secret' => '*****',
    'limit' => 50
]);
```

Get root (uploaded) photos from a page :

```php
new Streams\Facebook\Album('ChatNoirDesign', [
    'api' => '*****',
    'secret' => '*****',
    'limit' => 50,
    'type' => 'uploaded'
]);
```

Get videos from a page :

```php
new Streams\Facebook\Videos('ChatNoirDesign', [
    'api' => '*****',
    'secret' => '*****',
    'limit' => 50
]);
```

Get notes (articles) from a page :

```php
new Streams\Facebook\Notes('289941984496813', [
    'api' => '*****',
    'secret' => '*****',
    'limit' => 50
]);
```

### Notes

- support image and embed types
- there's no description on images
- notes cannot return a date
- do not support NSFW

## 500px

Get images from a user :

```php
new Streams\FiveHundredPx\User('ademgider', [
    'api' => '*****',
    'nsfw' => false,
    'limit' => 50
]);
```

Get images from a user's gallery :

```php
new Streams\FiveHundredPx\Gallery('ademgider/city-map', [
    'api' => '*****',
    'nsfw' => false,
    'limit' => 50
]);
```

### Notes

- support images

## Flickr

Get medias from a user :

```php
new Streams\Flickr\User('cannon_s5_is', [
    'api' => '*****',
    'limit' => 50
]);
```

Get medias from a user's album :

```php
new Streams\Flickr\Album('cannon_s5_is/72157625103228853', [
    'api' => '*****',
    'limit' => 50
]);
```

- support image and embed types

### Notes

- cannot get NSFW pictures, because they are not public

## Google Plus

Get medias from people :

```php
new Streams\GooglePlus\People('+frandroid', [
    'api' => '*****',
    'limit' => 50
]);
```

### Notes

- support text, image and embed types
- infinite pagination
- do not support NSFW

## Instagram

Get last images from a user :

```php
new Streams\Instagram\User('ademgider/city-map', [
    'limit' => 20
]);
```

### Notes

- support image and video types
- since 2016 June, it is impossible communicate with the API without authentifying the user; since Streams lies on public access (because we DO NOT want to display OAuth confirmations to the final user), we just can get the 20 last posts of an Instagram account
- do not support NSFW

## Reddit

Get medias from a subreddit :

```php
new Streams\Reddit\Subreddit('earthporn', [
    'nsfw' => false,
    'limit' => 50,
    // either 'popular', 'new' (default), 'rising', 'controversial', 'top' or 'gilded'
    'type' => 'new'
]);
```

Get medias from user's posts :

```php
new Streams\Reddit\User('hansiphoto', [
    'nsfw' => false,
    'limit' => 50
]);
```

### Notes

- support text, image and embed types
- the API can be a bit slow

## Vimeo

Get videos from a category :

```php
new Streams\Vimeo\Category('food', [
    'api' => '*****',
    'secret' => '*****',
    'nsfw' => false,
    'limit' => 50
]);
```

Get videos from a channel :

```php
new Streams\Vimeo\Channel('comedy', [
    'api' => '*****',
    'secret' => '*****',
    'nsfw' => false,
    'limit' => 50
]);
```

Get videos from a group :

```php
new Streams\Vimeo\Group('animation', [
    'api' => '*****',
    'secret' => '*****',
    'nsfw' => false,
    'limit' => 50
]);
```

Get videos from a user :

```php
new Streams\Vimeo\User('loispatino', [
    'api' => '*****',
    'secret' => '*****',
    'nsfw' => false,
    'limit' => 50
]);
```

### Notes

- support embed type

## Youtube

Get videos from a channel :

```php
new Streams\Youtube\Channel('UCCMxHHciWRBBouzk-PGzmtQ', [
    'api' => '*****',
    'limit' => 50
]);
```

Get videos from a playlist :

```php
new Streams\Youtube\Playlist('PLWmL9Ldoef0sKB07aXA1ekfyIqu-47rV6', [
    'api' => '*****',
    'limit' => 50
]);
```

### Notes

- support embed type
- do not support NSFW

Group requests
--------------

If you want to get several streams at once (and because it's more effective), you can use the Guzzle Pool class :

```php
// Add Facebook
$streams[] = function() {
    $stream = new Streams\Facebook\Album('10150205173893951', [
        'api' => '*****',
        'secret' => '*****',
        'limit' => 50
    ]);
    return $stream->get()->then(function($elements) {
        // Print all retrieved images
        foreach($elements as $element) {
            echo "<img src=\"{$element['source']}\" alt=\"{$element['title']}\">";
        }
    });
};

// Add Flickr
$streams[] = function() {
    $stream = new Streams\Flickr\Album('cannon_s5_is/72157625103228853', [
        'api' => '*****',
        'limit' => 50
    ]);
    return $stream->get()->then(function($elements) {
        // Print all retrieved images
        foreach($elements as $element) {
            echo "<img src=\"{$element['source']}\" alt=\"{$element['title']}\">";
        }
    });
};

// Add Instagram
$streams[] = function() {
    $stream = new Streams\Instagram\User('ladylikelilymusic');
    return $stream->get()->then(function($elements) {
        // Print all retrieved images
        foreach($elements as $element) {
            echo "<img src=\"{$element['source']}\" alt=\"{$element['title']}\">";
        }
    });
};

// Run requests
$guzzle = new GuzzleHttp\Client(['verify' => false]);
$pool = new GuzzleHttp\Pool($guzzle, $streams);
$pool->promise()->wait();
```

For more reading on concurrent requests with Guzzle, please read the [documentation](http://docs.guzzlephp.org/en/latest/quickstart.html#concurrent-requests).

License
-------

[MIT](http://dreamysource.mit-license.org).