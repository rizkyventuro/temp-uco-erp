<?php

return [
    'default' => 'main',
    'connections' => [
        'main' => [
            'salt'     => env('HASHIDS_SALT', 'fallback-salt'),
            'length'   => 4,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],
    ],
];
