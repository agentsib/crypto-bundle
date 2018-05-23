<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Encrypted
{

    /**
     * @var string
     */
    public $decryptedProperty;

    /**
     * @var bool
     */
    public $allowDecrypted = true;

    /**
     * @var string
     *
     * @Enum({"exception", "false"})
     */
    public $onDecryptFail = 'exception';

    /**
     * @var bool
     */
    public $nullable = false;
}