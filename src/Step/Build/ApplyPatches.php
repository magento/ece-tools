<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Step\ProcessException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ApplyPatches implements StepInterface
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
