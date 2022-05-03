<?php declare(strict_types=1);

return [
    'connections' => [
        'cache' => [
            'adapter' => 'phpredis',
            'host' => 'redis',
            'persistent' => true,
        ],
    ],
];
