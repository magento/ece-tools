<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Magento\SystemInterface;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;

/**
 * Validate that debug logging is disabled in Magento.
 */
class DebugLogging implements ValidatorInterface
{
    const CONFIG_PATH = 'dev/debug/debug_logging';

    /**
     * @var SystemInterface
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
     * @param SystemInterface $config
     * @param Environment $environment
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        SystemInterface $config,
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
            'To save space in Magento Cloud, disable debug logging for your production environments.',
            Error::WARN_DEBUG_LOG_ENABLED
        );
    }
}
