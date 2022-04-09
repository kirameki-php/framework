<?php declare(strict_types=1);

namespace Kirameki\Security;

use Kirameki\Core\Application;
use Kirameki\Core\Initializer;

class SecurityInitializer implements Initializer
{
    /**
     * @param Application $app
     */
    public function register(Application $app): void
    {
        $app->singleton(HashingManager::class, function () use ($app) {
            $config = $app->config('security.hashing');
            return new HashingManager($config);
        });

        $app->singleton(CryptoManager::class, function () use ($app) {
            $config = $app->config('security.crypto');
            return new CryptoManager($config);
        });
    }
}
