<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Command\ConfigDump\Generate;
use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for dumping SCD related config.
 */
class ConfigDump extends Command
{
    const NAME = 'config:dump';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var Generate
     */
    private $generate;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param LoggerInterface $logger
     * @param ShellFactory $shellFactory
     * @param Generate $generate
     * @param Reader $reader
     * @param Writer $writer
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        LoggerInterface $logger,
        ShellFactory $shellFactory,
        Generate $generate,
        Reader $reader,
        Writer $writer,
        MagentoVersion $magentoVersion
    ) {
        $this->logger = $logger;
        $this->shell = $shellFactory->createMagento();
        $this->generate = $generate;
        $this->reader = $reader;
        $this->writer = $writer;
        $this->magentoVersion = $magentoVersion;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Dump configuration for static content deployment.')
            ->setAliases(['dump']);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws GenericException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('Starting dump.');

        $envConfig = $this->reader->read();

        try {
            $this->shell->execute('app:config:dump');
        } finally {
            $this->writer->create($envConfig);
        }

        try {
            $this->generate->execute();

            if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
                $this->logger->info('Dump completed.');

                return 0;
            }

            $this->shell->execute('app:config:import');
        } catch (GenericException $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }

        $this->logger->info('Dump completed.');
    }
}
