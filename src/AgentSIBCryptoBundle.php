<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle;


use AgentSIB\CryptoBundle\DependencyInjection\AgentSIBCryptoExtension;
use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\RedisSecretSourceFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AgentSIBCryptoBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var AgentSIBCryptoExtension $extension */
        $extension = $this->getContainerExtension();

        $extension->addSecretSourceFactory(new RedisSecretSourceFactory());
    }

    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $class = $this->getContainerExtensionClass();
            $this->extension = new $class();
        }

        return $this->extension;
    }


}