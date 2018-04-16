<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\GlobalStage;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\CompositeValidator;

/**
 * @inheritdoc
 */
class ScdOnDeploy implements CompositeValidator
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @var DeployInterface
     */
    private $deployConfig;

    /**
     * @var ScdOnBuild
     */
    private $scdOnBuild;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param GlobalSection $globalSection
     * @param DeployInterface $deployConfig
     * @param ScdOnBuild $scdOnBuild
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        GlobalSection $globalSection,
        DeployInterface $deployConfig,
        ScdOnBuild $scdOnBuild
    ) {
        $this->resultFactory = $resultFactory;
        $this->globalConfig = $globalSection;
        $this->deployConfig = $deployConfig;
        $this->scdOnBuild = $scdOnBuild;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        if ($errors = $this->getErrors()) {
            return reset($errors);
        }

        return $this->resultFactory->success();
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): array
    {
        $errors = [];

        if ($this->globalConfig->get(StageConfigInterface::VAR_SCD_ON_DEMAND)) {
            $errors[] = $this->resultFactory->error('SCD_ON_DEMAND variable is enabled');
        }

        if ($this->deployConfig->get(DeployInterface::VAR_SKIP_SCD)) {
            $errors[] = $this->resultFactory->error('SKIP_SCD variable is enabled');
        }

        if ($this->scdOnBuild->validate() instanceof Validator\Result\Success) {
            $errors[] = $this->resultFactory->error('SCD on build is enabled');
        }

        return $errors;
    }
}
