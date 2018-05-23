<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CiphersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $cryptoServiceDefinition = $container->findDefinition('agentsib_crypto.crypto_service');

        $cipherDefinitions = $container->findTaggedServiceIds('agentsib_crypto.cipher');

        foreach ($cipherDefinitions as $serviceId => $tags) {
            $tag = current($tags);
            $version = $tag['version'];

            $cryptoServiceDefinition->addMethodCall('addCipherForVersion', [$version, new Reference($serviceId)]);
        }
    }
}
