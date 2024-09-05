<?php

namespace AgentSIB\CryptoBundle\Model;

interface CipherInterface
{
    /**
     * @param string $plainString
     * @return string
     */
    public function encrypt(string $plainString): string;

    /**
     * @param string $encryptedString
     * @return string
     */
    public function decrypt(string $encryptedString): string;
}
