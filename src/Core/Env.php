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
        $lowered = strtolower($value);
        if ($lowered === 'true') return true;
        if ($lowered === 'false') return false;
        if ($lowered === 'null') return null;
        return $value;
    }

    /**
     * @param string $filePath
     */
    public static function applyDotFile(string $filePath)
    {
        $content = file_get_contents($filePath);
        $lines = preg_split("/(\r\n|\n|\r)/", rtrim($content));
        foreach ($lines as $line) {
            dump($line);
            putenv($line);
        }
    }
}
