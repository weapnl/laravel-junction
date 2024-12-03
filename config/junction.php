<?php

return [
    'route' => [
        'media' => [
            /*
             * To enable the media upload endpoint, set this variable to true.
             * You need to have the spatie/media-library package installed, for this to work.
             */
            'enabled' => true,

            /*
             * To add a custom middleware around the media upload endpoint.
             */
            'middleware' => ['api'],

            /*
             * To prefix the media upload endpoint.
             */
            'prefix' => '',

            'filesystem_disk' => env('FILESYSTEM_DISK', 'local'),
        ],
    ],
];
