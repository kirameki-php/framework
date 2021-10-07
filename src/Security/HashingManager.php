<?php declare(strict_types=1);

namespace Kirameki\Security;

use Kirameki\Core\Config;

class HashingManager
{
    /**
     * @var string
     */
    public static string $defaultAlgorithm = PASSWORD_ARGON2ID;

    /**
     * @var string
     */
    protected string $algorithm;

    /**
     * @var string
     */
    protected string $pepper;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->algorithm = $config->getString('algorithm') ?? static::$defaultAlgorithm;
        $this->pepper = $config->getString('pepper');
    }

    /**
     * @param string $password
     * @return string
     */
    public function make(string $password): string
    {
        $password = $this->pepper.'.'.$password;
        return password_hash($password, $this->algorithm);
    }

    /**
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
