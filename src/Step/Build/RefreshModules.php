<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RefreshModules implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Module
     */
    private $config;

    /**
     * @param LoggerInterface $logger
     * @param Module $config
     */
    public function __construct(
        LoggerInterface $logger,
        Module $config
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->notice('Reconciling installed modules with shared config.');

        try {
            $enabledModules = $this->config->refresh();
            $this->logger->info(
                $enabledModules ?
                    'The following modules have been enabled:' . PHP_EOL . implode(PHP_EOL, $enabledModules) :
                    'No modules were changed.'
            );
        } catch (\Exception $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
        $this->logger->notice('End of reconciling modules.');
    }
}
