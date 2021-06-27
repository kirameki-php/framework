<?php

namespace Kirameki\Storage;

use Kirameki\Support\Collection;
use RuntimeException;
use function array_filter;
use function array_pop;
use function explode;
use function implode;
use function is_dir;
use function getcwd;
use function glob;
use function preg_match;
use function preg_quote;
use function realpath;
use function substr;

class LocalStorage implements StorageInterface
{
    /**
     * @var string
     */
    protected string $basePath;

    /**
     * @param string|null $basePath
     */
    public function __construct(string $basePath = null)
    {
        $this->basePath = $basePath ?? $this->asAbsolutePath(__DIR__.'/../../..');
    }

    /**
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $dirPath
     * @param bool $deep
     * @return Collection<Info>|Info[]
     */
    public function list(string $dirPath, bool $deep = false): Collection
    {
        $absDirPath = $this->asAbsolutePath("$this->basePath/$dirPath");

        if (!preg_match('/^'.preg_quote($this->basePath, '/').'/', $absDirPath)) {
            // TODO Better error
            throw new RuntimeException("Invalid path $dirPath is not a subdirectory of $this->basePath");
        }

        if (realpath($absDirPath) === false || !is_dir($absDirPath)) {
            // TODO Better error
            throw new RuntimeException("$absDirPath is not a valid directory");
        }

        $list = glob("$absDirPath/*", GLOB_NOSORT | GLOB_MARK);

        $infos = [];
        foreach ($list as $path) {
            $infos[] = (substr($path, -1) === '/')
                ? new DirectoryInfo(rtrim($path, '/'), $this->basePath)
                : new FileInfo($path, $this->basePath);
        }

        return new Collection($infos);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function asAbsolutePath(string $path): string
    {
        $cwd = getcwd();
        $path = $cwd.preg_replace('/^'.preg_quote($cwd, '/').'/', '', $path, 1);

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

    public function listFiles(string $path, bool $deep = false): Collection
    {

    }

    public function listDirectories(string $path, bool $deep = false): Collection
    {

    }

    public function exists(string $path): bool
    {
        // TODO: Implement exists() method.
    }

    public function read(string $path): string
    {
        // TODO: Implement read() method.
    }

    public function write(string $path, $content, int $permission = null, bool $append = false): void
    {
        // TODO: Implement write() method.
    }

    public function copy(string $path1, string $path2): void
    {
        // TODO: Implement copy() method.
    }

    public function move(string $source, string $destination): void
    {
        // TODO: Implement move() method.
    }

    public function delete(string $path): void
    {
        // TODO: Implement delete() method.
    }

    public function changePermission(string $path): void
    {
        // TODO: Implement changePermission() method.
    }

    public function changeOwner(string $owner): void
    {
        // TODO: Implement changeOwner() method.
    }
}
