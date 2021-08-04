<?php declare(strict_types=1);

namespace Kirameki\Support;

use Carbon\Carbon;
use function fileatime;
use function filectime;
use function filemtime;
use function filesize;
use function is_dir;
use function is_executable;
use function is_file;
use function is_link;
use function is_readable;
use function is_writable;
use function pathinfo;

class FileInfo
{
    /**
     * @var string
     */
    protected string $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function basename(): string
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }

    /**
     * @return string
     */
    public function dirname(): string
    {
        return pathinfo($this->path, PATHINFO_DIRNAME);
    }

    /**
     * @return string
     */
    public function filename(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * @return int
     */
    public function size(): int
    {
        return filesize($this->path);
    }

    /**
     * @return bool
     */
    public function isFile(): bool
    {
        return is_file($this->path);
    }

    /**
     * @return bool
     */
    public function isDirectory(): bool
    {
        return is_dir($this->path);
    }

    /**
     * @return bool
     */
    public function isLink(): bool
    {
        return is_link($this->path);
    }

    /**
     * @return bool
     */
    public function isReadable(): bool
    {
        return is_readable($this->path);
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return is_writable($this->path);
    }

    /**
     * @return bool
     */
    public function isExecutable(): bool
    {
        return is_executable($this->path);
    }

    /**
     * @return Carbon
     */
    public function lastModifiedTime(): Carbon
    {
        return Carbon::createFromTimestamp(filemtime($this->path));
    }

    /**
     * @return Carbon
     */
    public function lastChangedTime(): Carbon
    {
        return Carbon::createFromTimestamp(filectime($this->path));
    }

    /**
     * @return Carbon
     */
    public function lastAccessedTime(): Carbon
    {
        return Carbon::createFromTimestamp(fileatime($this->path));
    }
}
