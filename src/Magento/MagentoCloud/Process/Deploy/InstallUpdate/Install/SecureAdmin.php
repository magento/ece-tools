<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ShellInterface $shell
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell,
        MagentoVersion $magentoVersion
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->magentoVersion->isGreaterOrEqual('2.2')) {
            $this->logger->info('Setting secure admin');

            $command = "php ./bin/magento config:set web/secure/use_in_adminhtml 1";
            $command .= $this->environment->getVerbosityLevel();

            $this->shell->execute($command);
        }
    }
}
