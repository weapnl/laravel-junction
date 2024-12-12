<?php

return [
    'route' => [
        'index' => [
            /*
             * Always add an "order by" clause based on the model key name to the index query, if the query doesn't already have one.
             *
             * Possible values: false, true, "asc", "desc".
             */
            'enforce_order_by' => false,
        ],

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
        ],
    ],
];
