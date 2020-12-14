<?php

namespace Kirameki\Database\Concerns;

use Kirameki\Database\Connection;

/**
 * @mixin Connection
 */
trait States
{
    /**
     * @return $this
     */
    public function reconnect(): static
    {
        $this->disconnect();
        $this->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function connect(): static
    {
        $this->adapter->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function disconnect(): static
    {
        $this->adapter->disconnect();
        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->adapter->isConnected();
    }
}
