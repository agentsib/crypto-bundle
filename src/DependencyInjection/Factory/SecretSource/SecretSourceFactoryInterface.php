<?php

namespace AgentSIB\CryptoBundle\DependencyInjection\Factory\SecretSource;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface SecretSourceFactoryInterface
{
    /**
     * @param ContainerBuilder $container
     * @param string $sourceName
     * @param array|string $config
     *
     * @return string service_id
     */
    public function create(ContainerBuilder $container, string $sourceName, array|string $config): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param ArrayNodeDefinition $builder
     */
    public function addConfiguration(ArrayNodeDefinition $builder): void;
}
