<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Model\SecretSource;


use AgentSIB\CryptoBundle\Model\Exception\SecretSourceExtension;
use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class EnvironmentSecretSource implements SecretSourceInterface
{
    /** @var string */
    private $environmentName;

    public function __construct($environmentName)
    {
        $this->environmentName = $environmentName;
    }

    public function getSecret()
    {
        $value = getenv($this->environmentName);
        if ($value === false) {
            throw new SecretSourceExtension(sprintf('Environment %s not exists!', $this->environmentName));
        }
        return $value;
    }

}