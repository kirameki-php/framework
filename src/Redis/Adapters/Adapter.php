<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

interface Adapter
{
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
