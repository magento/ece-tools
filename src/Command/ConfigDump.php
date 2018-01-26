<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ConfigDump\Export;
use Magento\MagentoCloud\Process\ConfigDump\Generate;
use Magento\MagentoCloud\Process\ConfigDump\Import;

/**
 * CLI command for dumping SCD related config.
 */
class ConfigDump extends Command
{
    const NAME = 'config:dump';
    const OPTION_KEEP_CONFIG = 'keep-config';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Export
     */
    private $export;

    /**
     * @var Generate
     */
    private $generate;

    /**
     * @var Import
     */
    private $import;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param Export $export
     * @param Generate $generate
     * @param Import $import
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        Export $export,
        Generate $generate,
        Import $import,
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->export = $export;
        $this->generate = $generate;
        $this->import = $import;
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                static::OPTION_KEEP_CONFIG,
                null,
                InputOption::VALUE_NONE,
                'Prevents existing config being overwritten.'
            )
        ];
        $this->setName(static::NAME)
            ->setDescription('Dump configuration for static content deployment.')
            ->setAliases(['dump'])
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doExport = ! $input->getOption(static::OPTION_KEEP_CONFIG);
        $doImport = $this->magentoVersion->isGreaterOrEqual('2.2');
        try {
            $this->logger->info('Starting dump.');
            if ($doExport) {
                $this->export->execute();
            }
            $this->generate->execute();
            if ($doImport) {
                $this->import->execute();
            }
            $this->logger->info('Dump completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }
}
