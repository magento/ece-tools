<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install;

use League\Flysystem\Config;
use Magento\MagentoCloud\Config\RemoteStorage;
use Magento\MagentoCloud\Service\RemoteStorageFactory;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;

/**
 * Stores config on remote storage.
 */
class StoreConfig implements StepInterface
{
    private const PATH = 'config.json';

    /**
     * @var RemoteStorage
     */
    private $remoteStorageConfig;

    /**
     * @var RemoteStorageFactory
     */
    private $remoteStorageFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @param RemoteStorage $remoteStorageConfig
     * @param RemoteStorageFactory $remoteStorageFactory
     * @param LoggerInterface $logger
     * @param ReaderInterface $reader
     */
    public function __construct(
        RemoteStorage $remoteStorageConfig,
        RemoteStorageFactory $remoteStorageFactory,
        LoggerInterface $logger,
        ReaderInterface $reader
    ) {
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->remoteStorageFactory = $remoteStorageFactory;
        $this->logger = $logger;
        $this->reader = $reader;
    }

    /**
     * @inheritDoc
     */
    public function execute(): void
    {
        $adapter = $this->remoteStorageConfig->getAdapter();

        if (!$adapter) {
            return;
        }

        $config = json_encode(['install' => $this->reader->read()['install']]);

        $this->logger->info('Storing config in remote storage');

        try {
            $this->remoteStorageFactory->create(
                $adapter,
                $this->remoteStorageConfig->getConfig(),
                $this->remoteStorageConfig->getPrefix()
            )->write(self::PATH, $config, new Config([]));

            $this->logger->info(sprintf(
                'Install date was stored in remote storage "%s"',
                $adapter
            ));
        } catch (Exception $exception) {
            $this->logger->critical('Cannot store config in remote storage: ' . $exception->getMessage());

            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
