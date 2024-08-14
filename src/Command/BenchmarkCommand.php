<?php

namespace AgentSIB\CryptoBundle\Command;

use AgentSIB\CryptoBundle\Service\CryptoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BenchmarkCommand extends Command
{
    /** @var CryptoService */
    protected $cryptService;

    public function __construct(CryptoService $cryptService, string $name = null)
    {
        parent::__construct($name);
        $this->cryptService = $cryptService;
    }

    protected function configure()
    {
        $this->setName('agentsib_crypto:benchmark')
            ->setDescription('Benchmark cipher');

        $this->addOption('count', null, InputOption::VALUE_REQUIRED, 'Count operation', 1000);
        $this->addOption('length', null, InputOption::VALUE_REQUIRED, 'Data length (bytes)', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = intval($input->getOption('count'));
        $length = intval($input->getOption('length'));

        if ($count <= 0 || $length <= 0) {
            throw new \RuntimeException('Invalid input parameters');
        }

        $progress = new ProgressBar($output, $count);

        $encryptTime = 0;
        $decryptTime = 0;

        for ($i = 0; $i < $count; $i++) {
            $bytes = openssl_random_pseudo_bytes($length);

            $startTime = microtime(true);
            $data = $this->cryptService->encrypt($bytes);
            $encryptTime += microtime(true) - $startTime;

            $startTime = microtime(true);
            $this->cryptService->decrypt($data);
            $decryptTime += microtime(true) - $startTime;

            if ($i % intval($count / 100) == 0) {
                $progress->setProgress($i);
            }
        }

        $progress->finish();

        $output->writeln('');
        $output->writeln('');

        $output->writeln(sprintf('Encrypt: %s seconds, avg: %s per second', $encryptTime, round($count/$encryptTime, 3)));
        $output->writeln(sprintf('Decrypt: %s seconds, avg: %s per second', $decryptTime, round($count/$decryptTime, 3)));

        return self::SUCCESS;
    }
}
