<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;

/**
 * Validates on using deprecated variables in .magento.env.yaml.
 */
class StageConfigDeprecatedVariables implements ValidatorInterface
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
     * @var Schema
     */
    private $schema;

    /**
     * @param EnvironmentReader $environmentReader
     * @param Validator\ResultFactory $resultFactory
     * @param Schema $schema
     */
    public function __construct(
        EnvironmentReader $environmentReader,
        Validator\ResultFactory $resultFactory,
        Schema $schema
    ) {
        $this->environmentReader = $environmentReader;
        $this->resultFactory = $resultFactory;
        $this->schema = $schema;
    }

    /**
     * Validates if .magento.env.yaml contains deprecated variables described in @see Schema::getDeprecatedSchema()
     *
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $config = $this->environmentReader->read()[StageConfigInterface::SECTION_STAGE] ?? [];
        $deprecatedSchema = $this->schema->getDeprecatedSchema();
        $errors = [];

        foreach ($config as $stageConfig) {
            if (!is_array($stageConfig)) {
                continue;
            }
            foreach (array_keys($stageConfig) as $key) {
                if (!isset($deprecatedSchema[$key]) || isset($errors[$key])) {
                    continue;
                }

                $error = sprintf('The %s variable is deprecated.', $key);

                if (isset($deprecatedSchema[$key][Schema::SCHEMA_REPLACEMENT])) {
                    $error .= sprintf(' Use %s instead.', $deprecatedSchema[$key][Schema::SCHEMA_REPLACEMENT]);
                }

                $errors[$key] = $error;
            }
        }

        if ($errors) {
            return $this->resultFactory->create(Validator\ResultInterface::ERROR, [
                'error' => 'Some configurations in your .magento.env.yaml file is deprecated.',
                'suggestion' => implode(PHP_EOL, $errors),
            ]);
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}
