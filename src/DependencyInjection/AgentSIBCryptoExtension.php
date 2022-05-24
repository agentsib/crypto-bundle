<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection;

use AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource\SecretSourceFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ChildDefinition;

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

        if (class_exists(Application::class)) {
            $loader->load('console.xml');
        }

        $this->loadSecretSources($config['secret_sources'], $container);
        $this->loadCiphers($config['ciphers'], $container);

        $cryptoServiceDefinition = $container->getDefinition('agentsib_crypto.crypto_service');
        $cryptoServiceDefinition->replaceArgument(0, $config['current_cipher']);
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

    private function loadCiphers(array $config, ContainerBuilder $container)
    {
        $ciphersIds = $container->findTaggedServiceIds('agentsib_crypto.cipher.prototype');

        $ciphers = [];
        foreach ($ciphersIds as $cipherId => $tags) {
            $tag = current($tags);

            if (!isset($tag['alias'])) {
                throw new \InvalidArgumentException('You must set alias for cipher prototype service');
            }

            $alias = strtolower($tag['alias']);
            $ciphers[$alias] = $cipherId;
        }

        foreach ($config as $version => $cipherConfig) {
            if (!isset($ciphers[strtolower($cipherConfig['cipher'])])) {
                throw new \InvalidArgumentException(sprintf('Cipher "%s" not found', $cipherConfig['cipher']));
            }

            $cipherDefinition = class_exists('\Symfony\Component\DependencyInjection\ChildDefinition')
                ? new ChildDefinition($ciphers[strtolower($cipherConfig['cipher'])])
                : new DefinitionDecorator($ciphers[strtolower($cipherConfig['cipher'])]);
            $cipherDefinition->replaceArgument(0, new Reference(sprintf('agentsib_crypto.secret_source.%s', $cipherConfig['secret_source'])));

            $cipherDefinition->addTag('agentsib_crypto.cipher', array(
                'version'  =>  $version
            ));

            $cipherServiceId = 'agentsib_crypto.cipher.' . $version;

            $container->setDefinition($cipherServiceId, $cipherDefinition);
        }
    }

    public function getAlias()
    {
        return 'agentsib_crypto';
    }
}
