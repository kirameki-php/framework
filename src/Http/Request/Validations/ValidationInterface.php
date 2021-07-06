<?php declare(strict_types=1);

namespace Kirameki\Http\Request\Validations;

interface ValidationInterface
{
    public function validate(string $name, array $inputs): void;
}
