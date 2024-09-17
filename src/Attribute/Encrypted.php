<?php

namespace AgentSIB\CryptoBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Encrypted
{
    public string $decryptedProperty;
    public bool $allowDecrypted = true;

    /**
     * @var string
     *
     * @Enum({"exception", "false"})
     */
    public string $onDecryptFail = 'exception';

    public bool $nullable = false;

    public function __construct(
        string $decryptedProperty,
        bool $allowDecrypted = null,
        string $onDecryptFail = null,
        bool $nullable = null
    ) {
        $this->decryptedProperty = $decryptedProperty;
        $this->allowDecrypted = $allowDecrypted ?? $this->allowDecrypted;
        $this->onDecryptFail = $onDecryptFail ?? $this->onDecryptFail;
        $this->nullable = $nullable ?? $this->nullable;
    }
}
