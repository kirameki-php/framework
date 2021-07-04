<?php declare(strict_types=1);

namespace Kirameki\Container;

interface EntryInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return mixed
     */
    public function getInstance(): mixed;
}
