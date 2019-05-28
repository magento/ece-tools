<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Deploy\Reader as EnvReader;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Config\Shared\Reader as SharedReader;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;
use Magento\MagentoCloud\Filesystem\Writer\WriterInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Config\SearchEngine as SearchEngineConfig;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SearchEngine implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EnvWriter
     */
    private $envWriter;

    /**
     * @var SharedWriter
     */
    private $sharedWriter;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * Returns search configuration
     *
     * @var SearchEngineConfig
     */
    private $searchEngineConfig;

    /**
     * @var EnvReader
     */
    private $envReader;

    /**
     * @var SharedReader
     */
    private $sharedReader;

    /**
     * @param LoggerInterface $logger
     * @param EnvWriter $envWriter
     * @param EnvReader $envReader
     * @param SharedWriter $sharedWriter
     * @param SharedReader $sharedReader
     * @param MagentoVersion $version
     * @param SearchEngineConfig $searchEngineConfig
     */
    public function __construct(
        LoggerInterface $logger,
        EnvWriter $envWriter,
        EnvReader $envReader,
        SharedWriter $sharedWriter,
        SharedReader $sharedReader,
        MagentoVersion $version,
        SearchEngineConfig $searchEngineConfig
    ) {
        $this->logger = $logger;
        $this->envWriter = $envWriter;
        $this->envReader = $envReader;
        $this->sharedWriter = $sharedWriter;
        $this->sharedReader = $sharedReader;
        $this->magentoVersion = $version;
        $this->searchEngineConfig = $searchEngineConfig;
    }

    /**
     * Executes the process.
     *
     * @return void
     * @throws ProcessException
     */
    public function execute()
    {
        try {
            $config = $this->searchEngineConfig->getConfig();
            $engine = $this->searchEngineConfig->getName();

            $this->logger->info('Updating search engine configuration.');
            $this->logger->info('Set search engine to: ' . $engine);

            $isMagento21 = $this->magentoVersion->satisfies('2.1.*');

            // 2.1.x requires search config to be written to the shared config file: MAGECLOUD-1317
            if ($isMagento21) {
                $this->updateSearchConfiguration($config, $this->sharedReader, $this->sharedWriter);
            } else {
                $this->updateSearchConfiguration($config, $this->envReader, $this->envWriter);
            }
        } catch (GenericException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Unset previous search configuration and updates with new one from $searchConfig array
     *
     * @param array $searchConfig
     * @param ReaderInterface $reader
     * @param WriterInterface $writer
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function updateSearchConfiguration(array $searchConfig, ReaderInterface $reader, WriterInterface $writer)
    {
        $config = $reader->read();

        unset($config['system']['default']['smile_elasticsuite_core_base_settings']);
        unset($config['system']['default']['catalog']['search']);

        $writer->create(array_merge_recursive($config, $searchConfig));
    }
}
