<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PrepareConfig implements StepInterface
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

        if ($xFrameOptions = $this->globalConfig->get(GlobalConfig::VAR_X_FRAME_CONFIGURATION)) {
            $config['x-frame-options'] = (string)$xFrameOptions;
        }

        try {
            $this->configWriter->update($config);
        } catch (FileSystemException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
