<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\PackageManager;
use Psr\Log\LoggerInterface;

class SecureAdmin implements ProcessInterface
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
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var PackageManager
     */
    private $packageManager;

    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell,
        PackageManager $packageManager
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
        $this->packageManager = $packageManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->packageManager->hasMagentoVersion('2.2')) {
            $this->logger->info('Setting secure admin');

            $command = "php ./bin/magento config:set web/secure/use_in_adminhtml 1";
            $command .= $this->environment->getVerbosityLevel();

            $this->shell->execute($command);
        }
    }
}
