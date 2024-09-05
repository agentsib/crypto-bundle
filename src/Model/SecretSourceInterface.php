<?php

namespace AgentSIB\CryptoBundle\Model;

use AgentSIB\CryptoBundle\Model\Exception\SecretSourceExtension;

interface SecretSourceInterface
{
    /**
     * @throws SecretSourceExtension
     * @return string Secret
     */
    public function getSecret(): string;
}
