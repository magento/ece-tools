<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetCryptKey implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        ConfigReader $configReader,
        ConfigWriter $configWriter
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
    }

    /**
     * Update crypt/key in app/etc/env.php with CRYPT_KEY value from environment.
     * Will not change anything if the value is already set.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!empty($this->configReader->read()['crypt']['key'])) {
            return;
        }

        $key = $this->environment->getCryptKey();

        if (empty($key)) {
            return;
        }

        $this->logger->info('Setting encryption key');

        $config['crypt']['key'] = $key;

        try {
            $this->configWriter->update($config);
        } catch (FileSystemException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
