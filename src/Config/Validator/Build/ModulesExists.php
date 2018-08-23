<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\ConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Verifies of 'modules' section exists in configuration file.
 */
class ModulesExists implements ValidatorInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param ConfigInterface $config
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(ConfigInterface $config, Validator\ResultFactory $resultFactory)
    {
        $this->config = $config;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        return $this->config->has('modules')
            ? $this->resultFactory->success()
            : $this->resultFactory->error('The modules section is missing from the shared config file.');
    }
}
