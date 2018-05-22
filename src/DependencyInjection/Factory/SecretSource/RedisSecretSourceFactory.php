<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;


use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class RedisSecretSourceFactory implements SecretSourceFactoryInterface
{
    public function create(ContainerBuilder $container, $sourceName, $config = [])
    {
        $client = $config['client'];
        if (substr($client, 0, 1) == '@') {
            $client = substr($client, 1);
        }

        $secretSourceDefinition = new DefinitionDecorator('');
    }


    public function getName()
    {
        return 'redis';
    }

    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->arrayNode('redis')->info('Redis configuration')
                    ->children()
                        ->scalarNode('client')->cannotBeEmpty()->end()
                        ->scalarNode('key')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();
    }

}