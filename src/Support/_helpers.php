<?php declare(strict_types=1);

/**
 * @param string|object $class
 * @return string
 */
function class_basename(string|object $class): string
{
    $class = is_object($class) ? $class::class : $class;
    return basename(str_replace('\\', '/', $class));
}
