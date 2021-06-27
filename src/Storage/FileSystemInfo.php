<?php

namespace Kirameki\Storage;

use Carbon\Carbon;

abstract class FileSystemInfo
{
    /**
     * @var string
     */
    protected string $absolutePath;

    /**
     * @var string
     */
    protected string $basePath;

    /**
     * @param string $absolutePath
     * @param string $basePath
     */
    public function __construct(string $absolutePath, string $basePath)
    {
        $this->absolutePath = $absolutePath;
        $this->basePath = $basePath;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->absolutePath;
    }

    /**
     * @return string
     */
    public function relativePath(): string
    {
        $basePathSlash = rtrim($this->basePath, '/').'/';
        $pattern = '/^'.preg_quote($basePathSlash, '/').'/';
        return preg_replace($pattern, '', $this->absolutePath, 1);
    }

    /**
     * @return string
     */
    public function basename(): string
    {
        return pathinfo($this->absolutePath, PATHINFO_BASENAME);
    }

    /**
     * @return string
     */
    public function dirname(): string
    {
        return pathinfo($this->absolutePath, PATHINFO_DIRNAME);
    }

    /**
     * @return int
     */
    public function size(): int
    {
        return filesize($this->absolutePath);
    }

    /**
     * @return Carbon
     */
    public function lastModifiedTime(): Carbon
    {
        return Carbon::createFromTimestamp(filemtime($this->absolutePath));
    }

    /**
     * @return Carbon
     */
    public function lastChangedTime(): Carbon
    {
        return Carbon::createFromTimestamp(filectime($this->absolutePath));
    }

    /**
     * @return Carbon
     */
    public function lastAccessedTime(): Carbon
    {
        return Carbon::createFromTimestamp(fileatime($this->absolutePath));
    }

    /**
     * @return string
     */
    public function permissions(): string
    {
        return decoct(fileperms($this->absolutePath) & 0777);
    }

    /**
     * @return bool
     */
    public function isLink(): bool
    {
        return is_link($this->absolutePath);
    }
}
