<?php

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FileContentSecretSourceFactory implements SecretSourceFactoryInterface
{
    public function create(ContainerBuilder $container, string $sourceName, array|string $config): string
    {
        $secretSourceDefinition = new ChildDefinition('agentsib_crypto.secret_source.prototype.file_content');
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
        return 'file_content';
    }

    public function addConfiguration(ArrayNodeDefinition $builder): void
    {
        $builder
            ->children()
                ->scalarNode($this->getName())
                    ->info('Absolute file path')
                    ->cannotBeEmpty()
                ->end()
            ->end();
    }
}
