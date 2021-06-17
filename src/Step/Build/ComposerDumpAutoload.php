<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ComposerDumpAutoload implements StepInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var BuildInterface
     */
    private $stageConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShellInterface $shell
     * @param BuildInterface $stageConfig
     * @param LoggerInterface $logger
     */
    public function __construct(ShellInterface $shell, BuildInterface $stageConfig, LoggerInterface $logger)
    {
        $this->shell = $shell;
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            if ($this->stageConfig->get(BuildInterface::VAR_SKIP_COMPOSER_DUMP_AUTOLOAD)) {
                $this->logger->info(sprintf(
                    'The composer dump-autoload command was skipped as %s variable is set to true',
                    BuildInterface::VAR_SKIP_COMPOSER_DUMP_AUTOLOAD
                ));

                return;
            }
            $this->shell->execute('composer dump-autoload --optimize --apcu --ansi --no-interaction');
        } catch (ConfigException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_COMPOSER_DUMP_AUTOLOAD_FAILED, $e);
        }
    }
}
