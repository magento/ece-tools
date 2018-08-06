<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\ValidatorFactory;

/**
 * @inheritdoc
 */
class IdealState implements CompositeValidator
{
    /**
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @var ValidatorFactory $validatorFactory
     */
    private $validatorFactory;

    /**
     * @var ResultFactory $resultFactory
     */
    private $resultFactory;

    /**
     * @var array $errors
     */
    private $errors;

    /**
     * @param ResultFactory $resultFactory
     * @param ValidatorFactory $validatorFactory
     * @param GlobalSection $globalSection
     */
    public function __construct(
        ResultFactory $resultFactory,
        ValidatorFactory $validatorFactory,
        GlobalSection $globalConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->validatorFactory = $validatorFactory;
        $this->globalConfig = $globalConfig;
    }

    /**
     * @inheritdoc
     */
    public function validate(): ResultInterface
    {
        if ($this->getErrors()) {
            $suggestion = trim(array_reduce($this->getErrors(), function ($suggestion, $error) {
                return $suggestion . PHP_EOL . '  ' . $error->getError();
            }, ''));
            return $this->resultFactory->error('The configured state is not ideal', '  ' . $suggestion);
        }

        return $this->resultFactory->success();
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): array
    {
        if (!isset($this->errors)) {
            $this->errors = [];

            if (!$this->validatorFactory->create(GlobalStage\ScdOnBuild::class)->validate() instanceof Result\Success) {
                $this->errors[] = $this->resultFactory->error('The SCD is not set for the build stage');
            }

            if (!$this->validatorFactory->create(Deploy\PostDeploy::class)->validate() instanceof Result\Success) {
                $this->errors[] = $this->resultFactory->error('Post-deploy hook is not configured');
            }

            if (!$this->globalConfig->get(GlobalSection::VAR_SKIP_HTML_MINIFICATION)) {
                $this->errors[] = $this->resultFactory->error('Skip HTML minification is disabled');
            }
        }

        return $this->errors;
    }
}
