<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
class Setup implements ProcessInterface
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
     * @var File
     */
    private $file;

    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell,
        File $file
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
        $this->file = $file;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Saving disabled modules.');

        if (file_exists(Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing .regenerate flag');
            unlink(Environment::REGENERATE_FLAG);
        }

        try {
            /* Enable maintenance mode */
            $this->logger->notice('Enabling Maintenance mode.');
            $this->shell->execute("php ./bin/magento maintenance:enable {$this->environment->getVerbosityLevel()}");

            $this->logger->info('Running setup upgrade.');
            $this->shell->execute(
                "php ./bin/magento setup:upgrade --keep-generated -n {$this->environment->getVerbosityLevel()}"
            );

            /* Disable maintenance mode */
            $this->shell->execute(
                "php ./bin/magento maintenance:disable {$this->environment->getVerbosityLevel()}"
            );
            $this->logger->notice('Maintenance mode is disabled.');
        } catch (\RuntimeException $e) {
            //Rollback required by database
            throw new \RuntimeException($e->getMessage(), 6);
        }

        if ($this->file->isExists(Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing .regenerate flag');
            $this->file->deleteFile(Environment::REGENERATE_FLAG);
        }
    }
}
