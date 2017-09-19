<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
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

    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Setting secure admin');

        $command = "php ./bin/magento config:set web/secure/use_in_adminhtml 1";
        $command .= $this->environment->getVerbosityLevel();

        try {
            $this->shell->execute($command);
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }
    }
}
