<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;

/**
 * Cleans all cache by tags.
 */
class CleanCache implements StepInterface
{
    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var StageConfigInterface
     */
    private $stageConfig;

    /**
     * @var integer
     */
    private $errorCode;

    /**
     * @param ShellFactory $shellFactory
     * @param StageConfigInterface $stageConfig
     * @param int $errorCode
     */
    public function __construct(
        ShellFactory $shellFactory,
        StageConfigInterface $stageConfig,
        int $errorCode
    ) {
        $this->magentoShell = $shellFactory->createMagento();
        $this->stageConfig = $stageConfig;
        $this->errorCode = $errorCode;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->magentoShell->execute(
                'cache:flush',
                [$this->stageConfig->get(StageConfigInterface::VAR_VERBOSE_COMMANDS)]
            );
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), $this->errorCode, $e);
        } catch (ConfigException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
