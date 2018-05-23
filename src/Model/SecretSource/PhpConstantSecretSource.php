<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Model\SecretSource;

use AgentSIB\CryptoBundle\Model\Exception\SecretSourceExtension;
use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class PhpConstantSecretSource implements SecretSourceInterface
{
    private $constantName;

    public function __construct($constantName)
    {
        $this->constantName = $constantName;
    }

    public function getSecret()
    {
        if (!defined($this->constantName)) {
            throw new SecretSourceExtension(sprintf('PHP constant %s not exists!', $this->constantName));
        }

        return constant($this->constantName);
    }
}
