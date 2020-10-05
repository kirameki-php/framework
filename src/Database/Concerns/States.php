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
    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function connect()
    {
        $this->adapter->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function disconnect()
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
