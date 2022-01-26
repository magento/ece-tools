<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\RemoteStorage as RemoteStorageConfig;
use Psr\Log\LoggerInterface;

/**
 * Enable or disable remote storage during deployment.
 */
class RemoteStorage implements StepInterface
{
    /**
     * @var RemoteStorageConfig
     */
    private $config;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @param RemoteStorageConfig $config
     * @param MagentoVersion $magentoVersion
     * @param LoggerInterface $logger
     * @param WriterInterface $writer
     */
    public function __construct(
        RemoteStorageConfig $config,
        MagentoVersion $magentoVersion,
        LoggerInterface $logger,
        WriterInterface $writer
    ) {
        $this->config = $config;
        $this->magentoVersion = $magentoVersion;
        $this->logger = $logger;
        $this->writer = $writer;
    }

    /**
     * Enables or disables remote storage.
     *
     * @throws StepException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(): void
    {
        try {
            if (!$this->magentoVersion->isGreaterOrEqual('2.4.2')) {
                return;
            }
        } catch (UndefinedPackageException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($driver = $this->config->getDriver()) {
            $this->enableRemoteStorage($driver);

            $this->logger->info(sprintf('Remote storage driver set to: "%s"', $driver));
        } else {
            try {
                $this->writer->update(['remote_storage' => ['driver' => 'file']]);

                $this->logger->debug(sprintf('Remote storage driver was reset'));
            } catch (FileSystemException $exception) {
                $this->logger->critical(
                    $exception->getMessage(),
                    ['errorCode' => Error::WARN_REMOTE_STORAGE_CANNOT_BE_DISABLED]
                );

                throw new StepException(
                    $exception->getMessage(),
                    Error::WARN_REMOTE_STORAGE_CANNOT_BE_DISABLED,
                    $exception
                );
            }
        }
    }

    /**
     * Update configuration.
     *
     * @param string $driver
     * @throws StepException
     */
    private function enableRemoteStorage(string $driver): void
    {
        $config = $this->config->getConfig();

        if (empty($config['bucket']) || empty($config['region'])) {
            throw new StepException('Bucket and region are required configurations');
        }

        $data = [
            'remote_storage' => [
                'driver' => $driver,
                'config' => [
                    'bucket' => $config['bucket'],
                    'region' => $config['region']
                ]
            ],
        ];

        if ($prefix = $this->config->getPrefix()) {
            $data['remote_storage']['config']['prefix'] = $prefix;
        }

        if (isset($config['key'], $config['secret'])) {
            $data['remote_storage']['config']['credentials'] = [
                'key' => $config['key'],
                'secret' => $config['secret']
            ];
        }

        try {
            $this->writer->update($data);
        } catch (FileSystemException $exception) {
            $this->logger->critical(
                $exception->getMessage(),
                ['errorCode' => Error::WARN_REMOTE_STORAGE_CANNOT_BE_ENABLED]
            );

            throw new StepException(
                $exception->getMessage(),
                Error::WARN_REMOTE_STORAGE_CANNOT_BE_ENABLED,
                $exception
            );
        }
    }
}
