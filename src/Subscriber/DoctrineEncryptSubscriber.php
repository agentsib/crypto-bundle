<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Subscriber;


use AgentSIB\CryptoBundle\Annotation\Encrypted;
use AgentSIB\CryptoBundle\Model\Exception\CryptoException;
use AgentSIB\CryptoBundle\Model\Exception\DecryptException;
use AgentSIB\CryptoBundle\Service\CryptoService;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

class DoctrineEncryptSubscriber implements EventSubscriber
{
    const OPERATION_ENCRYPT = 'encrypt';
    const OPERATION_DECRYPT = 'decrypt';

    const ENCRYPTED_ANNOTATION = 'AgentSIB\CryptoBundle\Annotation\Encrypted';


    /** @var CryptoService */
    private $cryptoService;
    /** @var AnnotationReader */
    private $annotationReader;

    public function __construct(CryptoService $cryptoService, AnnotationReader $annotationReader)
    {
        $this->cryptoService = $cryptoService;
        $this->annotationReader = $annotationReader;
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

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->processFields($entity, self::OPERATION_ENCRYPT);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->processFields($entity, self::OPERATION_DECRYPT);
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->processFields($entity, self::OPERATION_DECRYPT);
    }

    public function preFlush(PreFlushEventArgs $args)
    {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->processFields($entity, self::OPERATION_ENCRYPT);
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->processFields($entity, self::OPERATION_DECRYPT);
        }
    }

    public function processFields($entity, $operation)
    {
        if(strstr(get_class($entity), "Proxies")) {
            $realClass = ClassUtils::getClass($entity);
        } else {
            $realClass = get_class($entity);
        }

        $reflectionClass = new \ReflectionClass($realClass);
        $properties = $this->getClassProperties($reflectionClass);

        foreach ($properties as $refProperty) {

            if ($this->annotationReader->getPropertyAnnotation($refProperty, 'Doctrine\ORM\Mapping\Embedded')) {
                $this->handleEmbeddedAnnotation($entity, $refProperty, $operation);
                continue;
            }

            /** @var \ReflectionProperty $refProperty */
            if ($annotation = $this->annotationReader->getPropertyAnnotation($refProperty, self::ENCRYPTED_ANNOTATION)) {
                /** @var Encrypted $annotation */
                $decryptedPropName = $annotation->decryptedProperty;
                $nullable = $annotation->nullable;
                $allowDecrypted = $annotation->allowDecrypted;
                $onDecryptFail = $annotation->onDecryptFail;

                if (!$refProperty->getDeclaringClass()->hasProperty($decryptedPropName)) {
                    throw new \Exception('Property %s not exists for class %s', $decryptedPropName, $refProperty->getDeclaringClass()->getNamespaceName());
                }

                $refDecryptedProperty = $refProperty->getDeclaringClass()->getProperty($decryptedPropName);
                $refEncryptedProperty = $refProperty;

                $encryptedPropValue = $this->getPropertyValue($entity, $refEncryptedProperty);
                $decryptedPropValue = $this->getPropertyValue($entity, $refDecryptedProperty);

                switch ($operation) {
                    case self::OPERATION_ENCRYPT:
                        try {
                            $currentValue = $encryptedPropValue;
                            if ($encryptedPropValue) {
                                $currentValue = $this->cryptoService->decrypt($encryptedPropValue);
                            }
                        } catch (DecryptException $e) {
                            if ($onDecryptFail == 'false') {
                                $currentValue = false;
                            } else {
                                throw $e;
                            }
                        }
                        if ($currentValue === false) {
                            $this->setPropertyValue($entity, $refEncryptedProperty, $currentValue);
                        } else {
                            if ($currentValue != $decryptedPropValue) {
                                if ($decryptedPropValue) {
                                    $encryptedPropValue = $this->cryptoService->encrypt($decryptedPropValue);
                                } else {
                                    $encryptedPropValue = $nullable ? null : '';
                                }
                            }
                        }

                        $this->setPropertyValue($entity, $refEncryptedProperty, $encryptedPropValue);
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
                            if ($onDecryptFail == 'false') {
                                $currentValue = false;
                            } else {
                                throw $e;
                            }
                        }
                        $this->setPropertyValue($entity, $refDecryptedProperty, $currentValue);
                        break;

                }

            }
        }

    }

    private function getPropertyValue($object, \ReflectionProperty $refProperty)
    {
        if ($refProperty->isPublic()) {
            $value = $refProperty->getValue($object);
        } else {
            $refProperty->setAccessible(true);
            $value = $refProperty->getValue($object);
            $refProperty->setAccessible(false);
        }

        return $value;
    }

    private function setPropertyValue($object, \ReflectionProperty $refProperty, $value)
    {
        if ($refProperty->isPublic()) {
            $refProperty->setValue($object, $value);
        } else {
            $refProperty->setAccessible(true);
            $refProperty->setValue($object, $value);
            $refProperty->setAccessible(false);
        }

    }

    private function handleEmbeddedAnnotation($entity, \ReflectionProperty $embeddedProperty, $operation)
    {
        if(strstr(get_class($entity), "Proxies")) {
            $realClass = ClassUtils::getClass($entity);
        } else {
            $realClass = get_class($entity);
        }
        $reflectionClass = new \ReflectionClass($realClass);
        $propName = $embeddedProperty->getName();
        $methodName = ucfirst($propName);
        if ($embeddedProperty->isPublic()) {
            $embeddedEntity = $embeddedProperty->getValue();
        } else {
            if ($reflectionClass->hasMethod($getter = 'get' . $methodName)) {
                //Get the information (value) of the property
                try {
                    $embeddedEntity = $entity->$getter();
                } catch(\Exception $e) {
                    $embeddedEntity = null;
                }
            }
        }
        if ($embeddedEntity) {
            $this->processFields($embeddedEntity, $operation);
        }
    }

    private function getClassProperties(\ReflectionClass $reflectionClass){

        $properties = $reflectionClass->getProperties();
        $propertiesArray = array();

        foreach($properties as $property){
            $propertyName = $property->getName();
            $propertiesArray[$propertyName] = $property;
        }

        if($parentClass = $reflectionClass->getParentClass()){
            $parentPropertiesArray = $this->getClassProperties($parentClass->getName());
            if(count($parentPropertiesArray) > 0)
                $propertiesArray = array_merge($parentPropertiesArray, $propertiesArray);
        }

        return $propertiesArray;
    }


}