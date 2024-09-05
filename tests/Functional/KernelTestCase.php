<?php

namespace AgentSIB\CryptoBundle\Tests\Functional;

use AgentSIB\CryptoBundle\Tests\Functional\app\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as BaseKernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelTestCase extends BaseKernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        static::deleteTmpDir();
    }

    public static function tearDownAfterClass(): void
    {
        static::deleteTmpDir();
    }

    protected static function deleteTmpDir()
    {
        if (!file_exists($dir = sys_get_temp_dir().'/'.static::getVarDir())) {
            return;
        }
        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected static function getKernelClass(): string
    {
        require_once __DIR__.'/app/AppKernel.php';

        return AppKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $class = self::getKernelClass();

        if (!isset($options['test_case'])) {
            throw new \InvalidArgumentException('The option "test_case" must be set.');
        }

        return new $class(
            static::getVarDir(),
            $options['test_case'],
            isset($options['root_config']) ? $options['root_config'] : 'config.yml',
            isset($options['environment']) ? $options['environment'] : strtolower(static::getVarDir().$options['test_case']),
            isset($options['debug']) ? $options['debug'] : true
        );
    }

    protected static function getVarDir()
    {
        return 'SB'.substr(strrchr(get_called_class(), '\\'), 1);
    }
}
