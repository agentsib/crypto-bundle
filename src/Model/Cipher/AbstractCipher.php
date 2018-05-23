<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Model\Cipher;

use AgentSIB\CryptoBundle\Model\CipherInterface;
use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

abstract class AbstractCipher implements CipherInterface
{
    /** @var SecretSourceInterface */
    protected $secretSource;

    public function __construct(SecretSourceInterface $secretSource)
    {
        $this->secretSource = $secretSource;
    }
}
