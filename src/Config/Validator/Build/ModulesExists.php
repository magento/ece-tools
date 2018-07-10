<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Shared;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Verifies of 'modules' section exists in configuration file.
 */
class ModulesExists implements ValidatorInterface
{
    /**
     * @var Shared
     */
    private $sharedConfig;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param Shared $sharedConfig
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(Shared $sharedConfig, Validator\ResultFactory $resultFactory)
    {
        $this->sharedConfig = $sharedConfig;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        return $this->sharedConfig->has('modules')
            ? $this->resultFactory->success()
            : $this->resultFactory->error('The modules section is missing from the shared config file.');
    }
}
