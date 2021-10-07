<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Container\Container;
use Kirameki\Database\DatabaseInitializer;
use Kirameki\Event\EventInitializer;
use Kirameki\Exception\ExceptionInitializer;
use Kirameki\Http\HttpInitializer;
use Kirameki\Logging\LogInitializer;
use Kirameki\Model\ModelInitializer;
use Kirameki\Security\SecurityInitializer;
use RuntimeException;

class Application extends Container
{
    /**
     * @var Application|null
     */
    protected static ?Application $instance;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $basePath;

    /**
     * @var float
     */
    protected float $startTime;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @return Application
     */
    public static function instance(): Application
    {
        return static::$instance;
    }

    /**
     * @param string $basePath
     * @param string|null $dotEnvPath
     */
    public function __construct(string $basePath, string $dotEnvPath = null)
    {
        static::$instance = $this;
        static::setPhpRuntimeConfigs();
        Env::applyDotFile($dotEnvPath ?? $basePath.'/.env');

        // getcwd() will be root project path
        chdir($basePath);

        $this->basePath = $basePath;
        $this->startTime = microtime(true) * 1000;
        $this->config = Config::fromDirectory($basePath.'/config');
        $this->setName($this->config->get('app.name'));
        $this->setTimeZone($this->config->get('app.timezone'));
        $this->initialize();
    }

    public static function setPhpRuntimeConfigs(): void
    {
        error_reporting(E_ALL & ~E_NOTICE);
        ini_set('display_errors', 'Off');
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        (new ExceptionInitializer)->register($this);
        (new LogInitializer)->register($this);
        (new EventInitializer)->register($this);
        (new DatabaseInitializer)->register($this);
        (new ModelInitializer)->register($this);
        (new HttpInitializer)->register($this);
        (new SecurityInitializer)->register($this);
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return file_get_contents(__DIR__.'/../VERSION');
    }

    /**
     * @return string
     */
    public function env(): string
    {
        return Env::get('APP_ENV') ?? 'production';
    }

    /**
     * @param string ...$names
     * @return bool
     */
    public function isEnv(string ...$names): bool
    {
        return in_array($this->env(), $names, true);
    }

    /**
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->isEnv('production');
    }

    /**
     * @return bool
     */
    public function isNotProduction(): bool
    {
        return !$this->isProduction();
    }

    /**
     * @return bool
     */
    public function runningInServer(): bool
    {
        return !$this->runningInConsole();
    }

    /**
     * @return bool
     */
    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * @return bool
     */
    public function inDebugMode(): bool
    {
        return (bool) $this->config->get('app.debug');
    }

    /**
     * @param string|null $relPath
     * @return string
     */
    public function getBasePath(string $relPath = null): string
    {
        $path = $this->basePath;
        if ($relPath !== null) {
            $path.= '/'.ltrim($relPath, '/');
        }
        return $path;
    }

    /**
     * @param string|null $relPath
     * @return string
     */
    public function getStoragePath(string $relPath = null): string
    {
        if ($relPath !== null) {
            $relPath.= '/'.ltrim($relPath, '/');
        }
        return $this->getBasePath('/storage/'.$relPath);
    }

    /**
     * @return float
     */
    public function startTime(): float
    {
        return $this->startTime;
    }

    /**
     * @param string|null $for
     * @return Config
     */
    public function config(string $for = null): Config
    {
        return $for !== null
            ? $this->config->for($for)
            : $this->config;
    }

    /**
     * @param string $name
     * @return void
     */
    protected function setName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            throw new RuntimeException('Invalid application name: "'.$name.'". Only alphanumeric characters, "_", and "-" are allowed.');
        }
        $this->name = $name;
    }

    /**
     * @param string $timezone
     * @return bool
     */
    protected function setTimeZone(string $timezone): bool
    {
        return date_default_timezone_set($timezone);
    }
}
