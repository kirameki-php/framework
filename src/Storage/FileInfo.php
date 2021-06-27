<?php

namespace Kirameki\Storage;

class FileInfo extends FileSystemInfo
{
    /**
     * @return string
     */
    public function filename(): string
    {
        return pathinfo($this->absolutePath, PATHINFO_FILENAME);
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return pathinfo($this->absolutePath, PATHINFO_EXTENSION);
    }
}
