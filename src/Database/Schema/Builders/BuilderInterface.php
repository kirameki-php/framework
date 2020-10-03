<?php

namespace Kirameki\Database\Schema\Builders;

interface BuilderInterface
{
    /**
     * @return string[]
     */
    public function toDdls(): array;
}
