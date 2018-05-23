<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Model;

interface CipherInterface
{
    /**
     * @param string $plainString
     * @return string
     */
    public function encrypt($plainString);

    /**
     * @param string $encryptedString
     * @return string
     */
    public function decrypt($encryptedString);
}
