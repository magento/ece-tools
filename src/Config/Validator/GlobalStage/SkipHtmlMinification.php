<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\GlobalStage;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * {@inheritdoc}
 */
class SkipHtmlMinification implements ValidatorInterface
{
    /**
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @var ResultFactory $resultFactory
     */
    private $resultFactory;

    public function __construct(GlobalSection $globalConfig, Validator\ResultFactory $resultFactory)
    {
        $this->globalConfig = $globalConfig;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        if (!$this->globalConfig->get(GlobalSection::VAR_SKIP_HTML_MINIFICATION)) {
            return $this->resultFactory->error(
                'Skip HTML minification is disabled',
                'Make sure "SKIP_HTML_MINIFICATION" is set to true in .magento.env.yaml.'
            );
        }

        return $this->resultFactory->success();
    }
}
