<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\EceToolExtend\Step\Build;

use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Customized step for enabling production mode
 */
class SetProductionMode implements StepInterface
{
    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param WriterInterface $writer
     */
    public function __construct(
        LoggerInterface $logger,
        WriterInterface $writer
    ) {
        $this->logger = $logger;
        $this->writer = $writer;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->logger->info('Customized step for enabling production mode');
            #Do some actions
            $this->writer->update(['MAGE_MODE' => 'production']);
        } catch (FileSystemException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
