<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Utils;

use Doctrine\Common\Util\ClassUtils as DocrineClassUtils;

class ClassUtils
{
    /**
     * @param $object
     * @param string|\ReflectionClass $property
     * @return mixed
     *
     * @throws \ReflectionException|\LogicException
     */
    public static function getPropertyValue($object, $property)
    {
        if ($property instanceof \ReflectionProperty) {
            $refClass = $property->getDeclaringClass();
            $refProperty = $property;
        } else {
            $refClass = new \ReflectionClass($object);
            $refProperty = $refClass->getProperty($property);
        }

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
     * @param $object
     * @param string|\ReflectionClass $property
     *
     * @throws \ReflectionException|\LogicException
     */
    public static function setPropertyValue($object, $property, $value)
    {
        if ($property instanceof \ReflectionProperty) {
            $refClass = $property->getDeclaringClass();
            $refProperty = $property;
        } else {
            $refClass = new \ReflectionClass($object);
            $refProperty = $refClass->getProperty($property);
        }

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

    public static function getEntityClass($entity)
    {
        if(strstr(get_class($entity), "Proxies")) {
            return DocrineClassUtils::getClass($entity);
        } else {
            return get_class($entity);
        }
    }
}
