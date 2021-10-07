<?php declare(strict_types=1);

namespace Kirameki\Security;

use Kirameki\Core\Config;
use RuntimeException;

class CryptoManager
{
    /**
     * @var string
     */
    public static string $defaultAlgorithm = 'aes-256-gcm';

    /**
     * @var int
     */
    public static int $tagSize = 16;

    /**
     * @var string
     */
    protected string $algorithm;

    /**
     * @var string
     */
    protected string $key;

    /**
     * @var int
     */
    protected int $ivSize;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->algorithm = $config->get('algorithm') ?? static::$defaultAlgorithm;
        $this->key = $config->get('key');
        $this->ivSize = openssl_cipher_iv_length($this->algorithm);

        if (($keySize = strlen($this->key)) !== 32) {
            throw new RuntimeException("Key for crypto should contain 32 characters. $keySize given.");
        }
    }

    /**
     * @param string $data
     * @return string
     */
    public function encrypt(string $data): string
    {
        $iv = openssl_random_pseudo_bytes($this->ivSize);
        $tag = null;
        $encrypted = openssl_encrypt($data, $this->algorithm, $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', static::$tagSize);
        return base64_encode($iv.$tag.$encrypted);
    }

    /**
     * @param string $data
     * @return bool
     */
    public function decrypt(string $data): string
    {
        $encrypted = base64_decode($data);
        $iv = substr($encrypted, 0, $this->ivSize);
        $tag = substr($encrypted, $this->ivSize, static::$tagSize);
        $data = substr($encrypted, $this->ivSize + static::$tagSize);

        return openssl_decrypt($data, $this->algorithm, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}
