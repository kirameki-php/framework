<?php

namespace Kirameki;

use Dotenv\Dotenv;
use InvalidArgumentException;
use Kirameki\Container\Container;
use Kirameki\Exceptions\ExceptionHandler;
use Kirameki\Exceptions\ExceptionInitializer;
use Kirameki\Logging\LogInitializer;
use Kirameki\Support\Config;
use Kirameki\Support\Env;

class Application extends Container
{
    protected static ?Application $instance;

    protected string $basePath;

    protected float $startTime;

    protected Config $config;

    public static function instance(): Application
    {
        return static::$instance;
    }

    public function __construct(string $basePath, string $dotEnvPath = null)
    {
        Dotenv::createImmutable([$dotEnvPath ?? $basePath])->load();
        static::$instance = $this;
        $this->basePath = $basePath;
        $this->startTime = microtime(true) * 1000;
        $this->config = Config::fromDirectory($basePath.'/config');
        $this->setTimeZone($this->config->get('app.timezone'));
        $this->initialize();
    }

    protected function initialize(): void
    {
        $initializers = [
            new ExceptionInitializer,
            new LogInitializer,
        ];
        foreach ($initializers as $initializer) {
            $initializer->register($this);
        }
    }

    public function version(): string
    {
        return file_get_contents(__DIR__.'/../VERSION');
    }

    public function env(): string
    {
        return Env::get('APP_ENV') ?? 'production';
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

    public function runningInServer(): bool
    {
        return !$this->runningInConsole();
    }

    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli';
    }

    public function inDebugMode(): bool
    {
        return (bool) $this->config->get('app.debug');
    }

    public function getBasePath(string $relPath = null): string
    {
        $path = $this->basePath;
        if ($relPath !== null) {
            $path.= '/'.ltrim($relPath, '/');
        }
        return $path;
    }

    public function startTime(): float
    {
        return $this->startTime;
    }

    /**
     * @param string|null $key
     * @param mixed|null $value
     * @return Config|mixed|null
     */
    public function config(string $key = null, $value = null)
    {
        $argCount = func_num_args();

        if ($argCount === 0) return $this->config;
        if ($argCount === 1) return $this->config->get($key);
        if ($argCount === 2) return $this->config->set($key, $value);

        $errorMessage = __METHOD__.'() should only have upto 2 arguments. '.$argCount.' given.';
        throw new InvalidArgumentException($errorMessage);
    }

    protected function setTimeZone(string $timezone): bool
    {
        return date_default_timezone_set($timezone);
    }
}
