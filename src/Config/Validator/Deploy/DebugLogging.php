<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Magento\System;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;

/**
 * Validate that debug logging is disabled in Magento.
 */
class DebugLogging implements ValidatorInterface
{
    const CONFIG_PATH = 'dev/debug/debug_logging';

    /**
     * @var System
     */
    private $config;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param System $config
     * @param Environment $environment
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        System $config,
        Environment $environment,
        ResultFactory $resultFactory
    ) {
        $this->config = $config;
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

        if (!$this->config->get(self::CONFIG_PATH)) {
            return $this->resultFactory->success();
        }

        return $this->resultFactory->error(
            'Debug logging is enabled in Magento',
            'To save space in Magento Cloud, '
            . 'debug logging should not be enabled for your production environments.'
        );
    }
}
