<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetMode implements ProcessInterface
{
    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param Writer $deployConfigWriter
     */
    public function __construct(
        LoggerInterface $logger,
        Writer $deployConfigWriter
    ) {
        $this->logger = $logger;
        $this->writer = $deployConfigWriter;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info("Set Magento application mode to 'production'");
        $this->writer->update(['MAGE_MODE' => 'production']);
    }
}
