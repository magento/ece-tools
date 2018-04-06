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
        foreach ($this->validateAll() as $result) {
            if ($result instanceof Validator\Result\Error) {
                return $result;
            }
        }

        return $this->resultFactory->success();
    }

    /**
     * @inheritdoc
     */
    public function validateAll(): array
    {
        $results = [];

        if ($this->globalConfig->get(BuildInterface::VAR_SCD_ON_DEMAND)) {
            $results[] = $this->resultFactory->error('SCD_ON_DEMAND variable is enabled', '');
        }

        if ($this->buildConfig->get(BuildInterface::VAR_SKIP_SCD)) {
            $results[] = $this->resultFactory->error('SKIP_SCD variable is enabled', '');
        }

        $validationResult = $this->configFileStructure->validate();

        if ($validationResult instanceof Validator\Result\Error) {
            $results[] = $validationResult;
        }

        return $results;
    }
}
