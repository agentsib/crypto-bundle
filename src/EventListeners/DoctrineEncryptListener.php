<?php

namespace AgentSIB\CryptoBundle\EventListeners;

use AgentSIB\CryptoBundle\Attribute\Encrypted;
use AgentSIB\CryptoBundle\Model\Exception\DecryptException;
use AgentSIB\CryptoBundle\Service\CryptoService;
use AgentSIB\CryptoBundle\Utils\ClassUtils;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Embedded;

class DoctrineEncryptListener
{
    public const OPERATION_ENCRYPT = 'encrypt';
    public const OPERATION_DECRYPT = 'decrypt';

    private CryptoService $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate,
            Events::postUpdate,
            Events::postLoad,
            Events::preFlush,
            Events::postFlush
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->processFields($entity, self::OPERATION_ENCRYPT);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->processFields($entity, self::OPERATION_DECRYPT);
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->processFields($entity, self::OPERATION_DECRYPT);
    }

    public function preFlush(PreFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->processFields($entity, self::OPERATION_ENCRYPT);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->processFields($entity, self::OPERATION_DECRYPT);
        }
    }

    public function processFields(object $entity, string $operation): void
    {
        $realClass = ClassUtils::getEntityClass($entity);

        $reflectionClass = new \ReflectionClass($realClass);
        $properties = $this->getClassProperties($reflectionClass);

        foreach ($properties as $refProperty) {
            $attributes = $refProperty->getAttributes(Embedded::class);
            $embeddedAttribute = null;

            if (!empty($attributes)) {
                $embeddedAttribute = $attributes[0]->newInstance();
            }

            if ($embeddedAttribute) {
                $this->handleEmbeddedAttribute($entity, $refProperty, $operation);
                continue;
            }

            $attributes = $refProperty->getAttributes(Encrypted::class);
            $encAttribute = null;

            if (!empty($attributes)) {
                $encAttribute = $attributes[0]->newInstance();
            }

            if ($encAttribute instanceof Encrypted) {
                $decryptedPropName = $encAttribute->decryptedProperty;
                $nullable = $encAttribute->nullable;
                $allowDecrypted = $encAttribute->allowDecrypted;
                $onDecryptFail = $encAttribute->onDecryptFail;

                if (!$refProperty->getDeclaringClass()->hasProperty($decryptedPropName)) {
                    throw new \Exception('Property %s not exists for class %s', $decryptedPropName, $refProperty->getDeclaringClass()->getNamespaceName());
                }

                $refDecryptedProperty = $refProperty->getDeclaringClass()->getProperty($decryptedPropName);
                $refEncryptedProperty = $refProperty;

                $encryptedPropValue = ClassUtils::getPropertyValue($entity, $refEncryptedProperty);
                $decryptedPropValue = ClassUtils::getPropertyValue($entity, $refDecryptedProperty);

                switch ($operation) {
                    case self::OPERATION_ENCRYPT:
                        try {
                            $currentValue = $encryptedPropValue;
                            if ($encryptedPropValue) {
                                $currentValue = $this->cryptoService->decrypt($encryptedPropValue);
                            }
                        } catch (DecryptException $e) {
                            if ($onDecryptFail === 'false') {
                                $currentValue = false;
                            } else {
                                throw $e;
                            }
                        }
                        if ($currentValue === false) {
                            ClassUtils::setPropertyValue($entity, $refEncryptedProperty, $currentValue);
                        } else {
                            if ($currentValue != $decryptedPropValue) {
                                if ($decryptedPropValue) {
                                    $encryptedPropValue = $this->cryptoService->encrypt($decryptedPropValue);
                                } else {
                                    $encryptedPropValue = $nullable ? null : '';
                                }
                            }
                        }

                        ClassUtils::setPropertyValue($entity, $refEncryptedProperty, $encryptedPropValue);
                        break;
                    case self::OPERATION_DECRYPT:
                        try {
                            $currentValue = $encryptedPropValue;
                            if ($encryptedPropValue) {
                                if ($this->cryptoService->isEncryptedString($encryptedPropValue) || !$allowDecrypted) {
                                    $currentValue = $this->cryptoService->decrypt($encryptedPropValue);
                                }
                            }
                        } catch (DecryptException $e) {
                            if ($onDecryptFail === 'false') {
                                $currentValue = false;
                            } else {
                                throw $e;
                            }
                        }
                        ClassUtils::setPropertyValue($entity, $refDecryptedProperty, $currentValue);
                        break;

                }
            }
        }
    }

    private function handleEmbeddedAttribute(object $entity, \ReflectionProperty $embeddedProperty, string $operation): void
    {
        $realClass = ClassUtils::getEntityClass($entity);
        $reflectionClass = new \ReflectionClass($realClass);
        $propName = $embeddedProperty->getName();
        $methodName = ucfirst($propName);
        $embeddedEntity = null;

        if ($embeddedProperty->isPublic()) {
            $embeddedEntity = $embeddedProperty->getValue();
        } else {
            if ($reflectionClass->hasMethod($getter = 'get' . $methodName)) {
                //Get the information (value) of the property
                try {
                    $embeddedEntity = $entity->$getter();
                } catch(\Exception) {
                    $embeddedEntity = null;
                }
            }
        }
        if ($embeddedEntity) {
            $this->processFields($embeddedEntity, $operation);
        }
    }

    private function getClassProperties(\ReflectionClass $reflectionClass): array
    {
        $properties = $reflectionClass->getProperties();
        $propertiesArray = [];

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertiesArray[$propertyName] = $property;
        }

        if ($parentClass = $reflectionClass->getParentClass()) {
            $parentPropertiesArray = $this->getClassProperties($parentClass);
            if (count($parentPropertiesArray) > 0) {
                $propertiesArray = array_merge($parentPropertiesArray, $propertiesArray);
            }
        }

        return $propertiesArray;
    }
}
