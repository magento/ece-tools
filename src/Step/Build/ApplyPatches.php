<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;

/**
 * @inheritdoc
 */
class ApplyPatches implements StepInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @param Manager $manager
     * @param BuildInterface $stageConfig
     */
    public function __construct(
        Manager $manager,
        BuildInterface $stageConfig
    ) {
        $this->manager = $manager;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute(): void
    {
        try {
            $qualityPatches = $this->stageConfig->get(BuildInterface::VAR_QUALITY_PATCHES);
            $this->manager->apply($qualityPatches);
        } catch (ConfigException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_PATCH_APPLYING_FAILED, $e);
        }
    }
}
