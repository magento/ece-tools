<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;

/**
 * Sets value of property directories/document_root_is_pub in true
 */
class DocumentRoot implements ProcessInterface
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
     * @param LoggerInterface $logger
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigWriter $configWriter
    ) {
        $this->logger = $logger;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('The value of the property \'directories/document_root_is_pub\' set as \'true\'');
        $this->configWriter->update(['directories' => ['document_root_is_pub' => true]]);
    }
}
