<?php

namespace Kirameki\Storage;

use Carbon\Carbon;

abstract class Info
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
     * @return Carbon
     */
    public function mtime(): Carbon
    {
        $timestamp = filemtime($this->absolutePath);
        return Carbon::createFromTimestamp($timestamp);
    }

    /**
     * @return Carbon
     */
    public function ctime(): Carbon
    {
        $timestamp = filectime($this->absolutePath);
        return Carbon::createFromTimestamp($timestamp);
    }

    /**
     * @return Carbon
     */
    public function atime(): Carbon
    {
        $timestamp = fileatime($this->absolutePath);
        return Carbon::createFromTimestamp($timestamp);
    }
}
