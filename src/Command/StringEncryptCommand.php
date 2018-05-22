<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StringEncryptCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('agentsib_crypto:encrypt')
            ->setDescription('Decrypt string');

        $this->addArgument('plainString', InputArgument::REQUIRED, 'Plain string');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            $this->getContainer()->get('agentsib_crypto.crypto_service')->encrypt(
                $input->getArgument('plainString')
            )
        );
    }
}