<?php declare(strict_types=1);

namespace Kirameki\Http\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public array|string $methods,
        public string $path,
        public ?string $name = null,
    )
    {
    }
}
