<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;


use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface SecretSourceFactoryInterface
{
    /**
     * @param ContainerBuilder $container
     * @param $sourceName
     * @param array $config
     *
     * @return string service_id
     */
    public function create(ContainerBuilder $container, $sourceName, $config = []);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param ArrayNodeDefinition $builder
     */
    public function addConfiguration(ArrayNodeDefinition $builder);
}