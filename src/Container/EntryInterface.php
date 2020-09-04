<?php

namespace Kirameki\Container;

interface EntryInterface
{
    public function getId(): string;

    public function getInstance();
}
