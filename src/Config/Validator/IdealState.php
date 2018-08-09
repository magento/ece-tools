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
        $errors = $this->getErrors();
        if ($errors) {
            $suggestion = trim(array_reduce($errors, function ($suggestion, $error) {
                $suggestion .= PHP_EOL . '  ' . $error->getError();
                $suggestion .= ($error->getSuggestion()) ? PHP_EOL . $error->getSuggestion() : '';
                return $suggestion;
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
        $errors = [];

        $scdBuildError = $this->validatorFactory->create(GlobalStage\ScdOnBuild::class)->validate();
        $postDeployError = $this->validatorFactory->create(Deploy\PostDeploy::class)->validate();

        if (!$scdBuildError instanceof Result\Success) {
            $errors[] = $scdBuildError;
        }

        if (!$postDeployError instanceof Result\Success) {
            $errors[] = $postDeployError;
        }

        if (!$this->globalConfig->get(GlobalSection::VAR_SKIP_HTML_MINIFICATION)) {
            $errors[] = $this->resultFactory->error('Skip HTML minification is disabled');
        }

        return $errors;
    }
}
