<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Model\Cipher;

use AgentSIB\CryptoBundle\Model\Exception\DecryptException;
use AgentSIB\CryptoBundle\Model\SecretSourceInterface;

class AES256OpenSSLCipher extends AbstractCipher
{
    /** @var string  */
    protected $algo = 'aes-256-cbc';
    /** @var string  */
    protected $hash = 'sha256';
    /** @var int  */
    protected $iterations = 4096;
    /** @var int  */
    protected $hmacSize = 32;
    /** @var int  */
    protected $keySize = 16;

    public function __construct(SecretSourceInterface $secretSource, $options = [])
    {
        parent::__construct($secretSource);

        foreach (['algo', 'hash', 'iterations'] as $param) {
            if (isset($options[$param])) {
                $this->$param = $param;
            }
        }
    }

    public function encrypt($plainString)
    {
        $ivSize = openssl_cipher_iv_length($this->algo);
        $iv     = random_bytes($ivSize);
        $keys    = hash_pbkdf2($this->hash, $this->secretSource->getSecret(), $iv, $this->iterations, $this->keySize * 2, true);
        $encKey  = mb_substr($keys, 0, $this->keySize, '8bit');
        $hmacKey = mb_substr($keys, $this->keySize, null, '8bit');

        $ciphertext = openssl_encrypt(
            $plainString,
            $this->algo,
            $encKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        $hmac = hash_hmac($this->hash, $iv . $ciphertext, $hmacKey, true);

        return $hmac . $iv . $ciphertext;
    }

    public function decrypt($encryptedString)
    {
        $hmac       = mb_substr($encryptedString, 0, $this->hmacSize, '8bit');
        $ivSize     = openssl_cipher_iv_length($this->algo);
        $iv         = mb_substr($encryptedString, $this->hmacSize, $ivSize, '8bit');
        $ciphertext = mb_substr($encryptedString, $ivSize + $this->hmacSize, null, '8bit');

        $keys    = hash_pbkdf2($this->hash, $this->secretSource->getSecret(), $iv, $this->iterations, $this->keySize * 2, true);
        $encKey  = mb_substr($keys, 0, $this->keySize, '8bit');
        $hmacKey = mb_substr($keys, $this->keySize, null, '8bit');

        $hmacNew = hash_hmac($this->hash, $iv . $ciphertext, $hmacKey, true);
        if (!hash_equals($hmac, $hmacNew)) {
            throw new DecryptException('Wrong secret!');
        }

        return openssl_decrypt(
            $ciphertext,
            $this->algo,
            $encKey,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
