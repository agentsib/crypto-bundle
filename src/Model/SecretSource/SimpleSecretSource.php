<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Model\SecretSource;

use AgentSIB\CryptoBundle\Model\Exception\SecretSourceExtension;
use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class SimpleSecretSource implements SecretSourceInterface
{
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function getSecret()
    {
        return $this->secret;
    }
}
