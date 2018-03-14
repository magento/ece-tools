<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PrepareConfig implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @param LoggerInterface $logger
     * @param ConfigWriter $configWriter
     * @param GlobalConfig $globalConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigWriter $configWriter,
        GlobalConfig $globalConfig
    ) {
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->globalConfig = $globalConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating env.php.');

        $config = [
            'static_content_on_demand_in_production' => (int)$this->globalConfig->get(GlobalConfig::VAR_SCD_ON_DEMAND),
            'force_html_minification' => (int)$this->globalConfig->get(GlobalConfig::VAR_SKIP_HTML_MINIFICATION),
        ];

        $this->configWriter->update($config);
    }
}
