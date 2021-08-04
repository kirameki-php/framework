<?php declare(strict_types=1);

namespace Kirameki\Support;

use DirectoryIterator;
use Kirameki\Support\Collection;
use RuntimeException;
use function array_filter;
use function explode;
use function getcwd;
use function implode;
use function is_dir;
use function preg_quote;
use function preg_replace;

class File
{
    /**
     * @param string $dirPath
     * @param bool $deep
     * @return Collection<FileInfo>
     */
    public static function list(string $dirPath, bool $deep = false): Collection
    {
        $dirPath = static::toAbsolutePath($dirPath);

        static::assertDirectory($dirPath);

        return (new Collection(static::scan($dirPath, $deep)))
            ->map(fn($path) => new FileInfo($path));
    }

    /**
     * @param string $path
     * @return string
     */
    public static function toAbsolutePath(string $path): string
    {
        $cwd = getcwd();
        $path = $cwd.'/'.preg_replace('/^'.preg_quote($cwd, '/').'/', '', $path, 1);
        $parts = array_filter(explode('/', $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' === $part) continue;
            if ('..' === $part) {
                if (array_pop($absolutes) === null) {
                    // TODO Better error
                    throw new RuntimeException("Invalid Directory $path. No parent exists. (Too many ..s)");
                }
            }
            else $absolutes[] = $part;
        }
        return '/'.implode('/', $absolutes);
    }

    /**
     * @param string $dirPath
     * @param bool $deep
     * @return array
     */
    protected static function scan(string $dirPath, bool $deep = false): array
    {
        $paths = [];
        foreach(new DirectoryIterator($dirPath) as $info) {
            if($info->isDot()) {
                continue;
            }
            if ($info->isDir()) {
                if ($deep) {
                    foreach (static::scan($info->getPathname()) as $innerPath) {
                        $paths[]= $innerPath;
                    }
                }
                continue;
            }
            $paths[]= $info->getPathname();
        }
        return $paths;
    }

    /**
     * @param string $path
     */
    protected static function assertDirectory(string $path): void
    {
        if (!is_dir($path)) {
            // TODO Better error
            throw new RuntimeException("$path is not a valid directory");
        }
    }
}
