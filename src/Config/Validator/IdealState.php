<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\ValidatorFactory;

/**
 * @inheritdoc
 */
class IdealState implements CompositeValidator
{
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
     */
    public function __construct(ResultFactory $resultFactory, ValidatorFactory $validatorFactory)
    {
        $this->resultFactory = $resultFactory;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(): ResultInterface
    {
        if ($errors = $this->getErrors()) {
            $suggestion = array_reduce($errors, function ($suggestion, Error $item) {
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
        $htmlMinificationError = $this->validatorFactory->create(GlobalStage\SkipHtmlMinification::class)->validate();

        if ($scdBuildError instanceof Result\Error) {
            $errors[] = $scdBuildError;
        }

        if ($postDeployError instanceof Result\Error) {
            $errors[] = $postDeployError;
        }

        if ($htmlMinificationError instanceof Result\Error) {
            $errors[] = $htmlMinificationError;
        }

        return $errors;
    }
}
