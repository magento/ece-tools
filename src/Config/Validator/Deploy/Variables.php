<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\SchemaValidator;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * @inheritdoc
 */
class Variables implements ValidatorInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param Environment $environment
     * @param SchemaValidator $schema
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(
        Environment $environment,
        SchemaValidator $schema,
        Validator\ResultFactory $resultFactory
    ) {
        $this->environment = $environment;
        $this->schemaValidator = $schema;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $variables = $this->environment->getVariables();
        $errors = [];

        foreach ($variables as $key => $value) {
            if ($error = $this->schemaValidator->validate($key, $value)) {
                $errors[] = $error;
            }
        }

        if ($errors) {
            return $this->resultFactory->create(Validator\Result\Error::ERROR, [
                'error' => 'Environment configuration is not valid',
                'suggestion' => implode(PHP_EOL, $errors),
            ]);
        }

        return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
    }
}
