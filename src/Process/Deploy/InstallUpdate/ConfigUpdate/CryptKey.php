<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CryptKey implements ProcessInterface
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
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        ConfigWriter $configWriter
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $key = $this->environment->getCryptKey();

        if (!empty($key)) {
            $this->logger->info('Setting encryption key');

            $config['crypt']['key'] = $key;

            $this->configWriter->update($config);
        }
    }
}
