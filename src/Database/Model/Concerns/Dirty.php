<?php

namespace Kirameki\Database\Model\Concerns;

use Kirameki\Database\Model\Model;
use DateTimeInterface;

/**
 * @mixin Model
 */
trait Dirty
{
    protected array $originalAttributes = [];

    public function getOriginalAttribute(string $name)
    {
        return $this->originalAttributes[$name] ?? null;
    }

    public function changedAttributes()
    {
        $changed = [];
        foreach ($this->attributes as $name => $value) {
            if ($this->wasChanged($name)) {
                $changed[$name] = $value;
            }
        }
        return $changed;
    }

    public function wasChanged(string $name)
    {
        $original = $this->originalAttributes[$name] ?? null;
        $current = $this->attributes[$name] ?? null;

        if ($original === null && $current === null) {
            return array_key_exists($name, $this->originalAttributes)
               xor array_key_exists($name, $this->attributes);
        }

        return ! $this->areValuesEqual($original, $current);
    }

    public function revertAttribute(string $name)
    {
        if (array_key_exists($name, $this->originalAttributes)) {
            $this->attributes[$name] = $this->originalAttributes;
        } else {
            unset($this->attributes[$name]);
        }
        return $this;
    }

    public function revertAttributes()
    {
        return $this->attributes = $this->originalAttributes;
    }

    protected function areValuesEqual($value1, $value2)
    {
        $value1 = $this->toComparableFormat($value1);
        $value2 = $this->toComparableFormat($value2);

        if ($value1 === $value2) {
            return true;
        }

        if (is_object($value1) && is_object($value2)) {
            // double equals on objects will compare inner values
            return $value1 == $value2;
        }

        return false;
    }

    protected function toComparableFormat($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(static::$dateFormat);
        }

        return $value;
    }
}
