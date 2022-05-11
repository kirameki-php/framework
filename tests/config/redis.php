<?php declare(strict_types=1);

return [
    'connections' => [
        'phpredis' => [
            'host' => 'redis',
            'persistent' => true,
        ],
        'phpredis-ng' => [
            'host' => 'redis-ng',
            'persistent' => true,
        ],
    ],
];
