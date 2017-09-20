<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\EnvironmentAdmin;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

class EnvConfiguration implements ProcessInterface
{
    /**
     * @var EnvironmentAdmin
     */
    private $environmentAdmin;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @param EnvironmentAdmin $environmentAdmin
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        EnvironmentAdmin $environmentAdmin,
        ConfigWriter $configWriter,
        LoggerInterface $logger
    ) {
        $this->environmentAdmin = $environmentAdmin;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $adminUrl = $this->environmentAdmin->getAdminUrl();
        if (empty($adminUrl)) {
            return;
        }
        $this->logger->info('Updating env.php backend front name.');
        $config['backend']['frontName'] = $adminUrl;
        $this->configWriter->update($config);
    }
}
