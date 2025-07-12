<?php

return [
    'route' => [
        'index' => [
            /*
             * Always add an "order by" clause based on the model key name to the index query, if the query doesn't already have one.
             */
            'enforce_order_by_model_key' => false,

            /*
             * Change the direction of the enforced "order by" of the model key.
             *
             * Possible values: "asc" or "desc".
             */
            'enforce_order_by_model_key_direction' => 'asc',
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

    /*
     * Wraps store, update, destroy and media upload logic
     * in a database transaction. If enabled, all DB operations
     * in those methods will be committed only if successful,
     * otherwise rolled back on failure. Ensures consistency
     * and integrity across multiple model operations.
     */
    'useDbTransactions' => false,
];
