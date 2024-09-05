<?php

namespace AgentSIB\CryptoBundle;

use AgentSIB\CryptoBundle\DependencyInjection\AgentSIBCryptoExtension;
use AgentSIB\CryptoBundle\DependencyInjection\Compiler\CiphersPass;
use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\ChainXORSecretSourceFactory;
use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\SimpleSecretSourceFactory;
use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\EnvironmentSecretSourceFactory;
use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\FileContentSecretSourceFactory;
use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\PhpConstantSecretSourceFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AgentSIBCryptoBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        /** @var AgentSIBCryptoExtension $extension */
        $extension = $this->getContainerExtension();

        $extension->addSecretSourceFactory(new SimpleSecretSourceFactory());
        $extension->addSecretSourceFactory(new PhpConstantSecretSourceFactory());
        $extension->addSecretSourceFactory(new FileContentSecretSourceFactory());
        $extension->addSecretSourceFactory(new EnvironmentSecretSourceFactory());
        $extension->addSecretSourceFactory(new ChainXORSecretSourceFactory());

        $container->addCompilerPass(new CiphersPass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $class = $this->getContainerExtensionClass();
            $this->extension = new $class();
        }

        return $this->extension;
    }
}
