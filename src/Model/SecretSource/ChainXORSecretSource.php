<?php

namespace AgentSIB\CryptoBundle\Model\SecretSource;

use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class ChainXORSecretSource implements SecretSourceInterface
{
    /**
     * @var SecretSourceInterface[]
     */
    private array $secretSources;

    public function __construct()
    {
        $this->secretSources = [];
    }

    public function addSecretSource(SecretSourceInterface $secretSource): void
    {
        $this->secretSources[] = $secretSource;
    }

    public function getSecret(): string
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

    private function xorStrings(string $str, string $key): string
    {
        $result = '';
        for ($i=0, $c = strlen($str); $i < $c;) {
            for ($j=0; ($j < strlen($key) && $i < strlen($str)); $j++,$i++) {
                $result .= $str[$i] ^ $key[$j];
            }
        }

        return $result;
    }
}
