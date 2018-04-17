<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\GlobalStage;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\CompositeValidator;
use Magento\MagentoCloud\Config\Stage\Build as BuildConfig;

/**
 * @inheritdoc
 */
class ScdOnBuild implements CompositeValidator
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
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var Validator\Build\ConfigFileStructure
     */
    private $configFileStructure;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param GlobalSection $globalStage
     * @param BuildConfig $buildConfig
     * @param Validator\Build\ConfigFileStructure $configFileStructure
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        GlobalSection $globalStage,
        BuildConfig $buildConfig,
        Validator\Build\ConfigFileStructure $configFileStructure
    ) {
        $this->resultFactory = $resultFactory;
        $this->globalConfig = $globalStage;
        $this->buildConfig = $buildConfig;
        $this->configFileStructure = $configFileStructure;
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

        if ($this->globalConfig->get(BuildInterface::VAR_SCD_ON_DEMAND)) {
            $errors[] = $this->resultFactory->error('SCD_ON_DEMAND variable is enabled');
        }

        if ($this->buildConfig->get(BuildInterface::VAR_SKIP_SCD)) {
            $errors[] = $this->resultFactory->error('SKIP_SCD variable is enabled');
        }

        $validationResult = $this->configFileStructure->validate();

        if ($validationResult instanceof Validator\Result\Error) {
            $errors[] = $validationResult;
        }

        return $errors;
    }
}
