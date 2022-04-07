<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class FileContentSecretSourceFactory implements SecretSourceFactoryInterface
{
    public function create(ContainerBuilder $container, $sourceName, $config = [])
    {
        $secretSourceDefinition = class_exists('\Symfony\Component\DependencyInjection\ChildDefinition')
            ? new ChildDefinition('agentsib_crypto.secret_source.prototype.file_content')
            : new DefinitionDecorator('agentsib_crypto.secret_source.prototype.file_content');
        $secretSourceDefinition->replaceArgument(0, $config);

        $serviceId = 'agentsib_crypto.secret_source.'.$sourceName;
        $container->setDefinition($serviceId, $secretSourceDefinition);

        return $serviceId;
    }

    public function getName()
    {
        return 'file_content';
    }

    public function addConfiguration(ArrayNodeDefinition $builder)
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
