<?php declare(strict_types=1);

return [
    'connections' => [
        'phpredis' => [
            'adapter' => 'phpredis',
            'host' => 'redis',
            'persistent' => true,
        ],
        'phpredis-ng' => [
            'adapter' => 'phpredis',
            'host' => 'redis-ng',
            'persistent' => true,
        ],
    ],
];
