<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\ShellException;
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
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_CONFIG_PHP_IS_NOT_WRITABLE, $e);
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_MODULE_ENABLE_COMMAND_FAILED, $e);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }

        $this->logger->notice('End of reconciling modules.');
    }
}
