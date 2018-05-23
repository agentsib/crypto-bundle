<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class EnvironmentSecretSourceFactory implements SecretSourceFactoryInterface
{
    public function create(ContainerBuilder $container, $sourceName, $config = [])
    {
        $secretSourceDefinition = new DefinitionDecorator('agentsib_crypto.secret_source.prototype.environment');
        $secretSourceDefinition->replaceArgument(0, $config);

        $serviceId = 'agentsib_crypto.secret_source.'.$sourceName;
        $container->setDefinition($serviceId, $secretSourceDefinition);

        return $serviceId;
    }

    public function getName()
    {
        return 'environment';
    }

    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode($this->getName())
                    ->info('Environment name')
                    ->cannotBeEmpty()
                ->end()
            ->end();
    }
}
