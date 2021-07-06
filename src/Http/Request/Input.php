<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Input
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
