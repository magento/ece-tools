<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Environment;

class EmailChecker implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
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

            throw new \RuntimeException($message);
        }
    }
}
