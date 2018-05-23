<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Model\SecretSource;

use AgentSIB\CryptoBundle\Model\Exception\SecretSourceExtension;
use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class ChainXORSecretSource implements SecretSourceInterface
{
    /**
     * @var SecretSourceInterface[]
     */
    private $secretSources;

    public function __construct()
    {
        $this->secretSources = [];
    }

    public function addSecretSource(SecretSourceInterface $secretSource)
    {
        array_push($this->secretSources, $secretSource);
    }

    public function getSecret()
    {
        $secret = false;
        foreach ($this->secretSources as $source) {
            if ($secret === false) {
                $secret = $source->getSecret();
            } else {
                $secret = $this->xorStrings($source->getSecret(), $secret);
            }
        }

        return hash('sha256', $secret, true);
    }

    private function xorStrings($str, $key)
    {
        $result = '';
        for ($i=0; $i < strlen($str);) {
            for ($j=0; ($j < strlen($key) && $i < strlen($str)); $j++,$i++) {
                $result .= $str{$i} ^ $key{$j};
            }
        }

        return $result;
    }
}
