<?php

namespace AgentSIB\CryptoBundle\Model\SecretSource;

use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class SimpleSecretSource implements SecretSourceInterface
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
