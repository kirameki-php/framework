<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

use Kirameki\Core\Config;
use Kirameki\Redis\Support\ScanResult;
use Kirameki\Redis\Support\SetOptions;
use Kirameki\Redis\Support\Type;

interface Adapter
{
    /**
     * @return Config
     */
    public function getConfig(): Config;

    /**
     * @return string
     */
    public function getPrefix(): string;

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix(string $prefix): static;

    /**
     * @return $this
     */
    public function connect(): static;

    /**
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * @return $this
     */
    public function reconnect(): static;

    /**
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * @param string $name
     * @param mixed $args
     * @return mixed
     */
    public function command(string $name, mixed ...$args): mixed;

    /**
     * @param string|null $pattern
     * @param int $count
     * @return ScanResult
     */
    public function scan(?string $pattern = null, ?int $count = null): ScanResult;

    /**
     * @param int $index
     * @return bool
     */
    public function select(int $index): bool;

    /**
     * @param string $key
     * @param mixed $value
     * @param SetOptions|null $options
     * @return mixed
     */
    public function set(string $key, mixed $value, SetOptions $options = null): mixed;

    /**
     * @return float
     */
    public function time(): float;

    /**
     * @param string $key
     * @return Type
     */
    public function type(string $key): Type;
}
