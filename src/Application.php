<?php

namespace Kirameki;

use Dotenv\Dotenv;
use Kirameki\Container\Container;
use Kirameki\Support\Env;

class Application extends Container
{
    protected const Version = '0.0.1';

    protected string $basePath;

    protected float $startTime;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->startTime = microtime(true);
        $this->loadEnvFile();
    }

    public function loadEnvFile(string $path = null): void
    {
        Dotenv::createImmutable([$path ?? $this->basePath])->load();
    }

    public function env(): string
    {
        return Env::get('KIRAMEKI_ENV') ?? 'production';
    }

    public function inDebugMode(): bool
    {
        return (bool) Env::get('APP_DEBUG');
    }

    public function isEnv(string ...$names): bool
    {
        return in_array($this->env(), $names, true);
    }

    public function isProduction(): bool
    {
        return $this->isEnv('production');
    }

    public function isNotProduction(): bool
    {
        return !$this->isProduction();
    }

    public function version(): string
    {
        return self::Version;
    }
}
