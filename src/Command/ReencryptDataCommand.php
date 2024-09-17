<?php

namespace AgentSIB\CryptoBundle\Command;

use AgentSIB\CryptoBundle\Attribute\Encrypted;
use AgentSIB\CryptoBundle\Service\CryptoService;
use AgentSIB\CryptoBundle\Utils\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReencryptDataCommand extends Command
{
    private CryptoService $cryptoService;
    private ManagerRegistry $registry;

    public function __construct(
        CryptoService $cryptoService,
        ManagerRegistry $registry,
        string $name = null
    ) {
        parent::__construct($name);
        $this->cryptoService = $cryptoService;
        $this->registry = $registry;
    }

    protected function configure(): void
    {
        $this
            ->setName('agentsib_crypto:reencrypt')
            ->setAliases(['agentsib_crypto:reecrypt'])
            ->setDescription('Re-encrypt doctrine data');

        $this->addOption('em', null, InputOption::VALUE_REQUIRED, 'Entity manager', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->registry->getManager($input->getOption('em'));
        /** @var ClassMetadata[] $emMetadata */
        $emMetadata = $em->getMetadataFactory()->getAllMetadata();

        foreach ($emMetadata as $entityMetadata) {
            if ($entityMetadata->isMappedSuperclass) {
                continue;
            }
            $properties = $this->getEncryptionableProperties($entityMetadata);

            if (count($properties) == 0) {
                continue;
            }

            if (!$entityMetadata->getIdentifier()) {
                continue;
            }

            $output->writeln(sprintf('Re-encrypt <info>%s</info> entities:', $entityMetadata->name));

            /** @var EntityRepository $entityRepository */
            $entityRepository = $em->getRepository($entityMetadata->name);

            $totalCount = $entityRepository->createQueryBuilder('s')->select('count(s)')->getQuery()->getSingleScalarResult();
            $iterator = $entityRepository->createQueryBuilder('s')->getQuery()->iterate();

            $progressBar = new ProgressBar($output, $totalCount);

            $entityReflectionClass = new \ReflectionClass($entityMetadata->name);
            $propertiesArray = $this->getEncryptionableProperties($entityMetadata);

            foreach ($iterator as $row) {
                $entity = current($row);
                foreach ($propertiesArray as $encryptedProperty => $decryptedProperty) {
                    $refDecryptedProperty = $entityReflectionClass->getProperty($decryptedProperty);
                    $refEncryptProperty = $entityReflectionClass->getProperty($encryptedProperty);

                    $curValue = ClassUtils::getPropertyValue($entity, $refDecryptedProperty);
                    if ($curValue == false) {
                        continue; // TODO More complex
                    }

                    $encValue = $this->cryptoService->encrypt($curValue);
                    ClassUtils::setPropertyValue($entity, $refEncryptProperty, $encValue);
                }
                $em->flush($entity);

                $em->detach($entity);
                unset($entity);
                gc_enable();
                gc_collect_cycles();
                $progressBar->advance(1);
            }
            $progressBar->finish();
            $output->writeln('');
        }

        $output->writeln('');
        $output->writeln('All done');

        return self::SUCCESS;
    }

    /**
     * @param $entityMetaData
     * @return \ReflectionProperty[]
     */
    protected function getEncryptionableProperties($entityMetaData): array
    {
        //Create reflectionClass for each metadata object
        $reflectionClass = new \ReflectionClass($entityMetaData->name);
        $propertyArray = $reflectionClass->getProperties();
        $properties = [];

        foreach ($propertyArray as $property) {
            $attributes = $property->getAttributes(Encrypted::class);
            $encAttribute = null;

            if (!empty($attributes)) {
                $encAttribute = $attributes[0]->newInstance();
            }

            if ($encAttribute instanceof Encrypted) {
                $properties[$property->getName()] = $encAttribute->decryptedProperty;
            }
        }

        return $properties;
    }
}
