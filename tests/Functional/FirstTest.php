<?php

namespace AgentSIB\CryptoBundle\Tests\Functional;

class FirstTest extends KernelTestCase
{
    public function testKernelLoad()
    {
        $kernel = self::createKernel(['test_case' => 'FirstCase', 'root_config' => 'config.yml']);
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->assertTrue($container->has('agentsib_crypto.crypto_service'));
    }
}
