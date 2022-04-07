<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class PhpConstantSecretSourceFactory implements SecretSourceFactoryInterface
{
    public function create(ContainerBuilder $container, $sourceName, $config = [])
    {
        $secretSourceDefinition = class_exists('\Symfony\Component\DependencyInjection\ChildDefinition')
            ? new ChildDefinition('agentsib_crypto.secret_source.prototype.php_constant')
            : new DefinitionDecorator('agentsib_crypto.secret_source.prototype.php_constant');
        $secretSourceDefinition->replaceArgument(0, $config);

        $serviceId = 'agentsib_crypto.secret_source.'.$sourceName;
        $container->setDefinition($serviceId, $secretSourceDefinition);

        return $serviceId;
    }

    public function getName()
    {
        return 'php_constant';
    }

    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode($this->getName())
                    ->info('PHP constant')
                    ->cannotBeEmpty()
                ->end()
            ->end();
    }
}
