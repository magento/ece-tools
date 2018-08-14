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
        if ($errors = $this->getErrors()) {
            $suggestion = array_reduce($errors, function ($suggestion, $item) {
                $suggestion .= $item->getError() . PHP_EOL;

                if ($itemSuggestion = $item->getSuggestion()) {
                    $suggestion .= array_reduce(explode(PHP_EOL, $itemSuggestion), function ($itemSuggestion, $line) {
                        return $itemSuggestion . '  ' . $line . PHP_EOL;
                    }, '');
                }

                return $suggestion . PHP_EOL;
            }, '');

            return $this->resultFactory->error('The configured state is not ideal', trim($suggestion));
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
            $errors[] = $this->resultFactory->error(
                'Skip HTML minification is disabled',
                'Make sure "SKIP_HTML_MINIFICATION" is set to true in .magento.env.yaml.'
            );
        }

        return $errors;
    }
}
