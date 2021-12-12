<?php declare(strict_types=1);

namespace Tests\Kirameki\Security;

use Kirameki\Security\CryptoManager;
use Tests\Kirameki\TestCase;

class CryptoManagerTest extends TestCase
{
    public function testEncryptDecrypt(): void
    {
        $crypto = $this->app->get(CryptoManager::class);
        $encrypting = 'my testing crypto!';
        $encrypted = $crypto->encrypt($encrypting);
        $decrypted = $crypto->decrypt($encrypted);

        self::assertEquals($encrypting, $decrypted);
    }

    public function testEncryptWithoutKey(): void
    {
        $this->expectErrorMessage('Cannot assign null to property Kirameki\Security\CryptoManager::$key of type string');

        $this->app->config()->set('security.crypto.key', null);

        $this->app->get(CryptoManager::class)->encrypt('my testing crypto!');
    }
}