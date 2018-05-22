<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection;

use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\SecretSourceFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    /** @var SecretSourceFactoryInterface[] */
    private $secretSourceFactories;

    /**
     * Configuration constructor.
     * @param SecretSourceFactoryInterface[] $secretSourcesFactories
     */
    public function __construct(array $secretSourcesFactories = [])
    {
        $this->secretSourceFactories = $secretSourcesFactories;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('agentsib_crypto');

        $secretSourcesPrototypeNode = $rootNode
            ->children()
                ->arrayNode('secret_sources')
                    ->performNoDeepMerging()
                    ->useAttributeAsKey('name')
//                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->info('Secret sources');

        $this->addSecretSourceSection($secretSourcesPrototypeNode);


        return $treeBuilder;
    }

    public function addSecretSourceSection(ArrayNodeDefinition $secretSourcesPrototypeNode)
    {
        foreach ($this->secretSourceFactories as $factory) {
            $factory->addConfiguration($secretSourcesPrototypeNode);
        }

        $secretSourcesPrototypeNode
            ->validate()
                ->ifTrue(function ($v) { return count($v) > 1; })
                ->thenInvalid('You must set only one secret source')
            ->end()
            ->validate()
                ->ifTrue(function ($v) { return count($v) == 0; })
                ->thenInvalid('At lease one secret source required')
            ->end();
    }

}