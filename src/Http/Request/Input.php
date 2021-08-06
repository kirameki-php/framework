<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Input
{
    /**
     * @var bool
     */
    public bool $required;

    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @param bool $required
     * @param string|null $name
     */
    public function __construct(bool $required = true, string $name = null)
    {
        $this->required = $required;
        $this->name = $name;
    }
}
