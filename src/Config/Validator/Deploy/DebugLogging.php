<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\MagentoConfigValidator;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;

/**
 * Validate that debug logging is disabled in Magento.
 */
class DebugLogging implements ValidatorInterface
{
    /**
     * @var MagentoConfigValidator
     */
    private $configValidator;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param MagentoConfigValidator $validator
     * @param Environment $environment
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        MagentoConfigValidator $validator,
        Environment $environment,
        ResultFactory $resultFactory
    ) {
        $this->configValidator = $validator;
        $this->environment = $environment;
        $this->resultFactory = $resultFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): ResultInterface
    {
        if (!$this->environment->isMasterBranch()) {
            return $this->resultFactory->success();
        }

        if ($this->configValidator->validate('dev/debug/debug_logging', '0', '0')) {
            return $this->resultFactory->success();
        }

        return $this->resultFactory->error('Debug logging is enabled in Magento');
    }
}
