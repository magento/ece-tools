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

/**
 * Stores config on remote storage.
 */
class StoreConfig implements StepInterface
{
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
     * @param RemoteStorage $remoteStorageConfig
     * @param RemoteStorageFactory $remoteStorageFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        RemoteStorage $remoteStorageConfig,
        RemoteStorageFactory $remoteStorageFactory,
        LoggerInterface $logger
    ) {
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->remoteStorageFactory = $remoteStorageFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(): void
    {
        $adapter = $this->remoteStorageConfig->getAdapter();

        try {
            if ($adapter) {
                $config = json_encode(['install' => ['date' => date('r')]]);

                $this->logger->debug('Storing config in remote storage');

                $this->remoteStorageFactory->create(
                    $adapter,
                    $this->remoteStorageConfig->getConfig(),
                    $this->remoteStorageConfig->getPrefix()
                )->write('config.json', $config, new Config([]));

                $this->logger->info(sprintf(
                    'Install date was stored in remote storage "%s"',
                    $adapter
                ));
            }
        } catch (Exception $exception) {
            $this->logger->critical('Cannot store config in remote storage: ' . $exception->getMessage());

            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
