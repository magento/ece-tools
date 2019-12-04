<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetCryptKey implements StepInterface
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
    public function execute(): void
    {
        if (!empty($this->configReader->read()['crypt']['key'])) {
            return;
        }

        $key = $this->environment->getCryptKey();

        if (empty($key)) {
            $this->logger->notice(
                'Crypt key missing. Add the crypt key to the "app/etc/env.php" file, or set the "CRYPT_KEY" '
                . 'environment variable in the Cloud Project web UI.'
            );

            return;
        }

        $this->logger->info('Setting encryption key');

        $config['crypt']['key'] = $key;

        try {
            $this->configWriter->update($config);
        } catch (FileSystemException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
