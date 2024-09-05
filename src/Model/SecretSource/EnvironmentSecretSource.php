<?php

namespace AgentSIB\CryptoBundle\Model\SecretSource;

use AgentSIB\CryptoBundle\Model\Exception\SecretSourceExtension;
use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class EnvironmentSecretSource implements SecretSourceInterface
{
    private ?string $environmentName;

    public function __construct(?string $environmentName)
    {
        $this->environmentName = $environmentName;
    }

    public function getSecret(): string
    {
        $value = getenv($this->environmentName);
        if ($value === false) {
            throw new SecretSourceExtension(sprintf('Environment %s not exists!', $this->environmentName));
        }
        return $value;
    }
}
