<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Storage\LocalStorage;
use Tests\Kirameki\TestCase;

class LocalStorageTest extends TestCase
{
    public function testList()
    {
        $storage = new LocalStorage();
        dump($storage->list('/'));
    }
}
