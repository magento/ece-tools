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
     * {@inheritdoc}
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            $errors = [];

            foreach ($this->schema->getSchema() as $optionName => $optionConfig) {
                if ($optionConfig[Schema::SCHEMA_TYPE] !== ['array'] ||
                    !in_array(StageConfigInterface::STAGE_DEPLOY, $optionConfig[Schema::SCHEMA_STAGE]) ||
                    !is_string($this->mergedConfig->get()[$optionName])
                ) {
                    continue;
                }

                json_decode($this->mergedConfig->get()[$optionName], true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = sprintf('%s (%s)', $optionName, json_last_error_msg());
                }
            }

            if ($errors) {
                return $this->resultFactory->error('Next variables can\'t be decoded: ' . implode(', ', $errors));
            }
        } catch (\Exception $e) {
            return $this->resultFactory->error('Can\'t read merged configuration: ' . $e->getMessage());
        }

        return $this->resultFactory->success();
    }
}
