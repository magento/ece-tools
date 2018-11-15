<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ApplyPatches implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param LoggerInterface $logger
     * @param Manager $manager
     */
    public function __construct(
        LoggerInterface $logger,
        Manager $manager
    ) {
        $this->logger = $logger;
        $this->manager = $manager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->notice('Applying patches.');

        try {
            $this->manager->applyAll();
        } catch (GenericException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
        $this->logger->notice('End of applying patches.');
    }
}
