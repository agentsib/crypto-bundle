<?php

namespace AgentSIB\CryptoBundle\Utils;

use Doctrine\Common\Util\ClassUtils as DoctrineClassUtils;

class ClassUtils
{
    /**
     * @param object $object
     * @param \ReflectionProperty $property
     * @return mixed
     *
     * @throws \ReflectionException|\LogicException
     */
    public static function getPropertyValue(object $object, \ReflectionProperty $property): mixed
    {
        $refClass = $property->getDeclaringClass();
        $refProperty = $property;

        if (!$refClass->isInstance($object)) {
            throw new \LogicException(sprintf(
                'Expected class is "%s", actual "%s"',
                $refClass->getName(),
                get_class($object)
            ));
        }

        if ($refProperty->isPublic()) {
            $value = $refProperty->getValue($object);
        } else {
            $refProperty->setAccessible(true);
            $value = $refProperty->getValue($object);
            $refProperty->setAccessible(false);
        }

        return $value;
    }

    /**
     * @param object $object
     * @param \ReflectionProperty $property
     * @param mixed $value
     * @throws \ReflectionException
     */
    public static function setPropertyValue(object $object, \ReflectionProperty $property, mixed $value): void
    {
        $refClass = $property->getDeclaringClass();
        $refProperty = $property;

        if (!$refClass->isInstance($object)) {
            throw new \LogicException(sprintf(
                'Expected class is "%s", actual "%s"',
                $refClass->getName(),
                get_class($object)
            ));
        }

        if ($refProperty->isPublic()) {
            $refProperty->setValue($object, $value);
        } else {
            $refProperty->setAccessible(true);
            $refProperty->setValue($object, $value);
            $refProperty->setAccessible(false);
        }
    }

    public static function getEntityClass(object $entity): string
    {
        if (str_contains(get_class($entity), "Proxies")) {
            return DoctrineClassUtils::getClass($entity);
        }

        return get_class($entity);
    }
}
