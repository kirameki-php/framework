<?php

namespace Kirameki\Storage;

use Carbon\Carbon;

interface InfoInterface
{
    /**
     * @return string
     */
    public function path(): string;

    /**
     * @return string
     */
    public function relativePath(): string;

    /**
     * @return string
     */
    public function basename(): string;

    /**
     * @return string
     */
    public function dirname(): string;

    /**
     * @return int
     */
    public function size(): int;

    /**
     * @return Carbon
     */
    public function lastModifiedTime(): Carbon;

    /**
     * @return Carbon
     */
    public function lastChangedTime(): Carbon;

    /**
     * @return Carbon
     */
    public function lastAccessedTime(): Carbon;
}
