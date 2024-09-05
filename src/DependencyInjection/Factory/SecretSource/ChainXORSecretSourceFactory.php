<?php

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ChainXORSecretSourceFactory implements SecretSourceFactoryInterface
{
    public function create(ContainerBuilder $container, string $sourceName, array|string $config): string
    {
        $secretSourceDefinition = new ChildDefinition('agentsib_crypto.secret_source.prototype.chain_xor');

        if (!is_array($config)) {
            $config = [$config];
        }

        foreach ($config as $item) {
            $secretSourceDefinition->addMethodCall('addSecretSource', [
                $container->findDefinition(sprintf('agentsib_crypto.secret_source.%s', $item))
            ]);
        }

        $serviceId = 'agentsib_crypto.secret_source.'.$sourceName;
        $container->setDefinition($serviceId, $secretSourceDefinition);

        return $serviceId;
    }

    public function getName(): string
    {
        return 'chain_xor';
    }

    public function addConfiguration(ArrayNodeDefinition $builder): void
    {
        $builder
            ->children()
                ->arrayNode($this->getName())->info('Array of secret sources')
                    ->normalizeKeys(true)
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')->end()
                ->end()
            ->end()
            ->validate()
                ->always(function ($v) {
                    if (is_array($v[$this->getName()]) && !count($v[$this->getName()])) {
                        unset($v[$this->getName()]);
                    }
                    return $v;
                })
            ->end();
    }
}
