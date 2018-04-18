<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\SchemaValidator;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;

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
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @param EnvironmentReader $environmentReader
     * @param Validator\ResultFactory $resultFactory
     * @param SchemaValidator $schemaValidator
     */
    public function __construct(
        EnvironmentReader $environmentReader,
        Validator\ResultFactory $resultFactory,
        SchemaValidator $schemaValidator
    ) {
        $this->environmentReader = $environmentReader;
        $this->resultFactory = $resultFactory;
        $this->schemaValidator = $schemaValidator;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $config = $this->environmentReader->read()[StageConfigInterface::SECTION_STAGE] ?? [];
        $errors = [];

        foreach ($config as $stageConfig) {
            foreach ($stageConfig as $key => $value) {
                if ($error = $this->schemaValidator->validate($key, $value)) {
                    $errors[] = $error;
                }
            }
        }

        if ($errors) {
            return $this->resultFactory->create(Validator\Result\Error::ERROR, [
                'error' => 'Environment configuration is not valid',
                'suggestion' => PHP_EOL . "\t" .implode(PHP_EOL . "\t", $errors) . PHP_EOL,
            ]);
        }

        return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
    }
}
