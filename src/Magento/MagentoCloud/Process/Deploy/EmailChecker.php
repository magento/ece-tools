<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Environment;

class EmailChecker implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->environment->getAdminEmail()) {
            $message = 'ADMIN_EMAIL not set during install!'
                . '  We need this variable set to send the password reset email.'
                . ' Please set ADMIN_EMAIL and retry deploy.';

            $this->logger->error($message);
            throw new \RuntimeException($message);
        }
    }
}
