<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\CryptoBundle\Command;


use AgentSIB\CryptoBundle\Annotation\Encrypted;
use AgentSIB\CryptoBundle\Subscriber\DoctrineEncryptSubscriber;
use AgentSIB\CryptoBundle\Utils\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReecryptDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('agentsib_crypto:reecrypt')
            ->setDescription('Reecrypt doctrine data');

        $this->addOption('em', null, InputOption::VALUE_REQUIRED, 'Entity manager', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager($input->getOption('em'));
        $cryptoService = $this->getContainer()->get('agentsib_crypto.crypto_service');

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

                    $encValue = $cryptoService->encrypt($curValue);
                    ClassUtils::setPropertyValue($entity, $refEncryptProperty, $encValue);
                }
                $em->flush($entity);

                $em->clear($entity);
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
    }

    /**
     * @param $entityMetaData
     * @return \ReflectionProperty[]
     */
    protected function getEncryptionableProperties($entityMetaData)
    {
        //Create reflectionClass for each meta data object
        $reflectionClass = New \ReflectionClass($entityMetaData->name);
        $propertyArray = $reflectionClass->getProperties();
        $properties    = [];
        foreach ($propertyArray as $property) {
            /** @var Encrypted $annotation */
            if ($annotation = $this->getContainer()->get('annotation_reader')->getPropertyAnnotation(
                $property,
                DoctrineEncryptSubscriber::ENCRYPTED_ANNOTATION
            )) {
                $properties[$property->getName()] = $annotation->decryptedProperty;
            }
        }
        return $properties;
    }

}