<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
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
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RemoteStorageConfig $config
     * @param MagentoShell $magentoShell
     * @param MagentoVersion $magentoVersion
     * @param LoggerInterface $logger
     */
    public function __construct(
        RemoteStorageConfig $config,
        MagentoShell $magentoShell,
        MagentoVersion $magentoVersion,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->magentoShell = $magentoShell;
        $this->magentoVersion = $magentoVersion;
        $this->logger = $logger;
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

        $driver = $this->config->getDriver();

        if ($driver) {
            $config = $this->config->getConfig();

            if (empty($config['bucket']) || empty($config['region'])) {
                throw new StepException('Bucket and region are required configurations');
            }

            $options = [
                '--remote-storage-driver=' . $driver,
                '--remote-storage-bucket=' . $config['bucket'],
                '--remote-storage-region=' . $config['region']
            ];

            if ($prefix = $this->config->getPrefix()) {
                $options['--remote-storage-prefix='] = $prefix;
            }

            if (isset($config['key'], $config['secret'])) {
                $options[] = '--remote-storage-access-key=' . $config['key'];
                $options[] = '--remote-storage-secret-key=' . $config['secret'];
            }

            try {
                $this->magentoShell->execute(sprintf(
                    'setup:config:set %s -n',
                    implode(' ', $options)
                ));
            } catch (ShellException $exception) {
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

            $this->logger->info(sprintf(
                'Remote storage with driver "%s" was enabled',
                $driver
            ));

            return;
        }

        $this->magentoShell->execute('setup:config:set --remote-storage-driver=file -n');
    }
}
