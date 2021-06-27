<?php

namespace Kirameki\Storage;

use Kirameki\Support\Collection;

interface StorageInterface
{
    /**
     * @param string $dirPath
     * @param bool $deep
     * @return Collection<FileInfo>|FileInfo[]
     */
    public function list(string $dirPath, bool $deep = false): Collection;

    /**
     * @param string $path
     * @param bool $deep
     * @return Collection<FileInfo>|FileInfo[]
     */
    public function listFiles(string $path, bool $deep = false): Collection;

    /**
     * @param string $path
     * @param bool $deep
     * @return Collection<FileInfo>|FileInfo[]
     */
    public function listDirectories(string $path, bool $deep = false): Collection;

    /**
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * @param string $path
     * @return string
     */
    public function read(string $path): string;

    /**
     * @param string $path
     * @param $content
     * @param int|null $permission
     * @param bool $append
     * @return void
     */
    public function write(string $path, $content, int $permission = null, bool $append = false): void;

    /**
     * @param string $path1
     * @param string $path2
     * @return void
     */
    public function copy(string $path1, string $path2): void;

    /**
     * @param string $source
     * @param string $destination
     * @return void
     */
    public function move(string $source, string $destination): void;

    /**
     * @param string $path
     */
    public function delete(string $path): void;

    /**
     * @param string $path
     * @return void
     */
    public function changePermission(string $path): void;

    /**
     * @param string $owner
     * @return void
     */
    public function changeOwner(string $owner): void;
}
