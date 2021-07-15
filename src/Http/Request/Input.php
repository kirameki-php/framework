<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Input
{
    const FROM_ANY = 'any';
    const FROM_CONTENT = 'content';
    const FROM_QUERY = 'query';

    /**
     * @var bool
     */
    public bool $required;

    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var string|null
     */
    public ?string $from;

    /**
     * @param bool $required
     * @param string|null $name
     * @param string $from
     */
    public function __construct(bool $required, string $name = null, string $from = self::FROM_ANY)
    {
        $this->required = $required;
        $this->name = $name;
        $this->from = $from;
    }
}
