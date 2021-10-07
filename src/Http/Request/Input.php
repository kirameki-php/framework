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
    public string|null $name;

    /**
     * @var string|null
     */
    public string|null $arrayOf;

    /**
     * @param bool $required
     * @param string|null $name
     * @param string|null $arrayOf
     */
    public function __construct(bool $required = true, string $name = null, string $arrayOf = null)
    {
        $this->required = $required;
        $this->name = $name;
        $this->arrayOf = $arrayOf;
    }
}
