<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

interface BuilderInterface
{
    /**
     * @return string[]
     */
    public function toDdls(): array;
}
