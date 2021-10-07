<?php declare(strict_types=1);

namespace Kirameki\Core;

class Env
{
    /**
     * @param string $key
     * @return bool|string|null
     */
    public static function get(string $key): bool|string|null
    {
        $value = getenv($key);

        if ($value === false) {
            return null;
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }

    /**
     * @param string $filePath
     */
    public static function applyDotFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $lines = preg_split("/(\r\n|\n|\r)/", rtrim($content));
        foreach ($lines as $line) {
            putenv($line);
        }
    }
}
