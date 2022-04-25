<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

use Kirameki\Core\Config;

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
     * @param array<mixed> $args
     * @return mixed
     */
    public function command(string $name, array $args): mixed;
}
