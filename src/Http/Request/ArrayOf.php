<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayOf
{
    /**
     * @var string
     */
    public string $type;

    /**
     * @var bool
     */
    public bool $nullable;

    /**
     * @param string $type
     * @param bool $nullable
     */
    public function __construct(string $type, bool $nullable = false)
    {
        $this->type = $type;
        $this->nullable = $nullable;
    }
}
