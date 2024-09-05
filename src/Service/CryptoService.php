<?php

namespace AgentSIB\CryptoBundle\Service;

use AgentSIB\CryptoBundle\Model\CipherInterface;
use AgentSIB\CryptoBundle\Model\Exception\CryptoException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CryptoService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $currentCipherVersion;
    /** @var CipherInterface[] */
    private array $ciphers = [];

    public function __construct(string $currentCipherVersion)
    {
        $this->currentCipherVersion = $currentCipherVersion;
    }

    public function addCipherForVersion(string $version, CipherInterface $cipher): void
    {
        $this->ciphers[$version] = $cipher;
    }

    /**
     * @param string $plainString
     * @return string
     * @throws CryptoException
     */
    public function encrypt(string $plainString): string
    {
        try {
            if (!isset($this->ciphers[$this->currentCipherVersion])) {
                throw new CryptoException(sprintf('Cipher version "%s" not found', $this->currentCipherVersion));
            }
            $currentCipher = $this->ciphers[$this->currentCipherVersion];

            $encryptedString = base64_encode($currentCipher->encrypt($plainString));

            return 'enc:' . $this->currentCipherVersion . '::' . $encryptedString;
        } catch (CryptoException $e) {
            if ($this->logger) {
                $this->logger->critical(sprintf(
                    '%s: Encrypt filed. %s',
                    get_class($e),
                    $e->getMessage()
                ));
            }
            throw $e;
        }
    }

    /**
     * @param string $encryptedString
     * @return string
     * @throws CryptoException
     */
    public function decrypt(string $encryptedString): string
    {
        try {
            if (!preg_match('/^enc:(v[0-9]+):(.+)$/', $encryptedString, $matcher)) {
                throw new CryptoException('Invalid encrypted string');
            }
            $version = $matcher[1];
            $encryptedString = $matcher[2];

            if (!isset($this->ciphers[$version])) {
                throw new CryptoException(sprintf('Cipher version "%s" not found', $version));
            }

            $currentCipher = $this->ciphers[$version];

            $encryptedString = base64_decode($encryptedString);

            if ($encryptedString === false) {
                throw new CryptoException('Invalid encrypted string');
            }

            return $currentCipher->decrypt($encryptedString);
        } catch (CryptoException $e) {
            if ($this->logger) {
                $this->logger->critical(sprintf(
                    '%s: Decrypt filed. %s',
                    get_class($e),
                    $e->getMessage()
                ));
            }
            throw $e;
        }
    }

    /**
     * @param string $encryptedString
     * @param bool $checkVersion
     * @return bool
     */
    public function isEncryptedString(string $encryptedString, bool $checkVersion = true): bool
    {
        if (preg_match('/^enc:(v[0-9]+)::(.+)$/', (string)$encryptedString, $matcher)) {
            $version = $matcher[1];
            if ($checkVersion) {
                return isset($this->ciphers[$version]);
            }

            return true;
        }

        return false;
    }
}
