<?php

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SimpleSecretSourceFactory implements SecretSourceFactoryInterface
{
    public function create(ContainerBuilder $container, string $sourceName, array|string $config): string
    {
        $secretSourceDefinition = new ChildDefinition('agentsib_crypto.secret_source.prototype.simple');
        $secretSourceDefinition->replaceArgument(0, $config);

        if (is_array($config)) {
            throw new \InvalidArgumentException('Environment secret source does not support array configuration');
        }

        $serviceId = 'agentsib_crypto.secret_source.' . $sourceName;
        $container->setDefinition($serviceId, $secretSourceDefinition);

        return $serviceId;
    }

    public function getName(): string
    {
        return 'simple';
    }

    public function addConfiguration(ArrayNodeDefinition $builder): void
    {
        $builder
            ->children()
                ->scalarNode($this->getName())
                    ->info('Simple string value')
                    ->cannotBeEmpty()
                ->end()
            ->end();
    }
}
