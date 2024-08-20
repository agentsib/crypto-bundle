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
    private array $secretSourceFactories;

    /**
     * Configuration constructor.
     * @param SecretSourceFactoryInterface[] $secretSourcesFactories
     */
    public function __construct(array $secretSourcesFactories = [])
    {
        $this->secretSourceFactories = $secretSourcesFactories;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('agentsib_crypto');
        $rootNode = $treeBuilder->getRootNode();

        $secretSourcesPrototypeNode = $rootNode
            ->children()
                ->arrayNode('secret_sources')
                    ->performNoDeepMerging()
                    ->useAttributeAsKey('name')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->info('Secret sources');

        $this->addSecretSourceSection($secretSourcesPrototypeNode);

        $rootNode
            ->children()
                ->arrayNode('ciphers')
                    ->useAttributeAsKey('name')
                    ->isRequired()
                    ->performNoDeepMerging()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('cipher')->isRequired()->end()
                            ->scalarNode('secret_source')->isRequired()->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v) {
                            foreach (array_keys($v) as $key) {
                                if (!preg_match('/^v[0-9]+/', $key)) {
                                    return true;
                                }
                            }
                            return false;
                        })
                        ->thenInvalid('Key of cipher format error. Expected: "v[0-9]+"')
                    ->end()

                ->end()
            ->end();

        $rootNode
            ->children()
                ->scalarNode('current_cipher')
                ->info('Cipher for encrypt data "v[0-9]+"')
                ->isRequired()
                ->example('v1, v2, v3')
                ->validate()
                    ->ifTrue(function ($v) {
                        return !preg_match('/^v[0-9]+$/', $v);
                    })
                    ->thenInvalid('Value of current_cipher cipher must has format "v[0-9]+"')
                ->end()
            ->end();

        return $treeBuilder;
    }

    public function addSecretSourceSection(ArrayNodeDefinition $secretSourcesPrototypeNode)
    {
        foreach ($this->secretSourceFactories as $factory) {
            $factory->addConfiguration($secretSourcesPrototypeNode);
        }

        $secretSourcesPrototypeNode
            ->validate()
                ->ifTrue(function ($v) {
                    return count($v) > 1;
                })
                ->thenInvalid('You must set only one secret source')
            ->end()
            ->validate()
                ->ifTrue(function ($v) {
                    return count($v) == 0;
                })
                ->thenInvalid('At lease one secret source required')
            ->end();
    }
}
