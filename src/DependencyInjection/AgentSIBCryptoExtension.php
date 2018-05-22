<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection;


use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\SecretSourceFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\Resource\FileResource;

class AgentSIBCryptoExtension extends Extension
{
    /** @var SecretSourceFactoryInterface[] */
    private $secretSourceFactories = [];

    public function addSecretSourceFactory(SecretSourceFactoryInterface $factory)
    {
        $this->secretSourceFactories[$factory->getName()] = $factory;
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);

        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->loadSecretSources($config['secret_sources'], $container);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        $reflected = new \ReflectionClass($this);
        $namespace = $reflected->getNamespaceName();

        $class = $namespace.'\\Configuration';
        if (class_exists($class)) {
            $r = new \ReflectionClass($class);
            $container->addResource(new FileResource($r->getFileName()));

            return new $class($this->secretSourceFactories);
        }
    }


    private function loadSecretSources(array $config, ContainerBuilder $container)
    {
        foreach ($config as $secretSourceName => $secretSourceConfig) {
            $factoryName = key($secretSourceConfig);
            $factory = $this->secretSourceFactories[$factoryName];

            $serviceId = $factory->create($container, $secretSourceName, $secretSourceConfig[$factoryName]);
            $container->findDefinition($serviceId)->addTag('agentsib_crypto.secret_source');
        }
    }

    public function getAlias()
    {
        return 'agentsib_crypto';
    }




}