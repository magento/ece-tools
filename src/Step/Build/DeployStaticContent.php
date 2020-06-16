<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContent implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var ScdOnBuild
     */
    private $scdOnBuild;

    /**
     * @var StepInterface[]
     */
    private $steps;

    /**
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     * @param ScdOnBuild $scdOnBuild
     * @param StepInterface[] $steps
     */
    public function __construct(
        LoggerInterface $logger,
        FlagManager $flagManager,
        ScdOnBuild $scdOnBuild,
        array $steps
    ) {
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->scdOnBuild = $scdOnBuild;
        $this->steps = $steps;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->flagManager->delete(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);

            $result = $this->scdOnBuild->validate();

            if ($result instanceof Error) {
                $this->logger->notice('Skipping static content deploy: ' . $result->getError());

                return;
            }

            $this->logger->notice('Generating fresh static content');

            foreach ($this->steps as $step) {
                $step->execute();
            }

            $this->flagManager->set(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
            $this->logger->notice('End of generating fresh static content');
        } catch (StepException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
