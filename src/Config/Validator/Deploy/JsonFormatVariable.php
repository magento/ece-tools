<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\Deploy\MergedConfig;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Checks that array-type variables given as json string can be decoded into array.
 */
class JsonFormatVariable implements ValidatorInterface
{
    /**
     * @var MergedConfig
     */
    private $mergedConfig;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param MergedConfig $mergedConfig
     * @param Schema $schema
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        MergedConfig $mergedConfig,
        Schema $schema
    ) {
        $this->resultFactory = $resultFactory;
        $this->mergedConfig = $mergedConfig;
        $this->schema = $schema;
    }

    /**
     * Checks that array-type variables given as json string can be decoded into array.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $errors = [];

        foreach ($this->schema->getSchema() as $optionName => $optionConfig) {
            if ($optionConfig[Schema::SCHEMA_TYPE] !== ['array'] ||
                 !in_array(StageConfigInterface::STAGE_DEPLOY, $optionConfig[Schema::SCHEMA_STAGE]) ||
                 !is_string($this->mergedConfig->get()[$optionName])
            ) {
                continue;
            }

            if (!$this->isConfigCanBeDecoded($optionName)) {
                $errors[] = sprintf('%s (%s)', $optionName, json_last_error_msg());
            }
        }

        if ($errors) {
            return $this->resultFactory->error('Next variables can\'t be decoded: ' . implode(', ', $errors));
        }

        return $this->resultFactory->success();
    }

    /**
     * Checks that variable can be decoded.
     *
     * @param string $optionName
     * @return bool
     */
    private function isConfigCanBeDecoded(string $optionName)
    {
        try {
            json_decode($this->mergedConfig->get()[$optionName], true);

            return json_last_error() === JSON_ERROR_NONE;
        } catch (\Exception $e) {
        }

        return false;
    }
}
