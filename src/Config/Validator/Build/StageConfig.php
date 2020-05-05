<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\Schema\Validator;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Environment\ReaderInterface as EnvironmentReader;

/**
 * Validates 'stage' section of environment configuration.
 */
class StageConfig implements ValidatorInterface
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var Validator
     */
    private $schemaValidator;

    /**
     * @param EnvironmentReader $environmentReader
     * @param ResultFactory $resultFactory
     * @param Validator $schemaValidator
     */
    public function __construct(
        EnvironmentReader $environmentReader,
        ResultFactory $resultFactory,
        Validator $schemaValidator
    ) {
        $this->environmentReader = $environmentReader;
        $this->resultFactory = $resultFactory;
        $this->schemaValidator = $schemaValidator;
    }

    /**
     * @inheritdoc
     */
    public function validate(): ResultInterface
    {
        $config = $this->environmentReader->read()[StageConfigInterface::SECTION_STAGE] ?? [];
        $errors = [];

        foreach ($config as $stage => $stageConfig) {
            if (!is_array($stageConfig)) {
                continue;
            }
            foreach ($stageConfig as $key => $value) {
                $result = $this->schemaValidator->validate($key, $stage, $value);

                if ($result instanceof Error) {
                    $errors[] = $result->getError();
                }
            }
        }

        if ($errors) {
            return $this->resultFactory->error(
                'Environment configuration is not valid. Correct the following items in your .magento.env.yaml file:',
                implode(PHP_EOL, $errors),
                AppError::BUILD_WRONG_CONFIGURATION_MAGENTO_ENV_YAML
            );
        }

        return $this->resultFactory->success();
    }
}
