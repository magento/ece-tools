<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Command\ConfigDump\Generate;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for dumping SCD related config.
 *
 * @api
 */
class ConfigDump extends Command
{
    public const NAME = 'config:dump';

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
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var PostDeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param ShellFactory $shellFactory
     * @param Generate $generate
     * @param ReaderInterface $reader
     * @param WriterInterface $writer
     * @param MagentoVersion $magentoVersion
     * @param PostDeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ShellFactory $shellFactory,
        Generate $generate,
        ReaderInterface $reader,
        WriterInterface $writer,
        MagentoVersion $magentoVersion,
        PostDeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->shell = $shellFactory->createMagento();
        $this->generate = $generate;
        $this->reader = $reader;
        $this->writer = $writer;
        $this->magentoVersion = $magentoVersion;
        $this->stageConfig = $stageConfig;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Starting dump.');

        $envConfig = $this->reader->read();

        try {
            $this->shell->execute(
                'app:config:dump',
                [$this->stageConfig->get(PostDeployInterface::VAR_VERBOSE_COMMANDS)]
            );
        } finally {
            $this->writer->create($envConfig);
        }

        try {
            $this->generate->execute();

            if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
                $this->logger->info('Dump completed.');

                return 0;
            }

            $this->shell->execute(
                'app:config:import',
                [$this->stageConfig->get(PostDeployInterface::VAR_VERBOSE_COMMANDS)]
            );
        } catch (GenericException $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }

        $this->logger->info('Dump completed.');

        return Cli::SUCCESS;
    }
}
