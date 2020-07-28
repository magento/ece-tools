<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;

/**
 * Sets value of property directories/document_root_is_pub in true
 */
class DocumentRoot implements StepInterface
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
        try {
            $this->logger->info('The value of the property \'directories/document_root_is_pub\' set as \'true\'');
            $this->configWriter->update(['directories' => ['document_root_is_pub' => true]]);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
