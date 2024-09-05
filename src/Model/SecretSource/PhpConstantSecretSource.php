<?php

namespace AgentSIB\CryptoBundle\Model\SecretSource;

use AgentSIB\CryptoBundle\Model\Exception\SecretSourceExtension;
use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class PhpConstantSecretSource implements SecretSourceInterface
{
    private string $constantName;

    public function __construct(string $constantName)
    {
        $this->constantName = $constantName;
    }

    public function getSecret(): string
    {
        if (!defined($this->constantName)) {
            throw new SecretSourceExtension(sprintf('PHP constant %s not exists!', $this->constantName));
        }

        return constant($this->constantName);
    }
}
