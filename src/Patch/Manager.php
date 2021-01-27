<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Patch;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Wrapper for applying required patches.
 */
class Manager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @param ShellInterface $shell
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        MagentoVersion $magentoVersion
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Applies all needed patches.
     *
     * @throws ShellException
     * @throws ConfigException
     */
    public function apply(): void
    {
        if ($this->magentoVersion->isGitInstallation()) {
            $this->logger->info('Git-based installation. Skipping patches applying');

            return;
        }

        $this->logger->notice('Applying patches');

        try {
            $this->shell->execute('php ./vendor/bin/ece-patches apply --no-interaction');
        } catch (ShellException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        $this->logger->notice('End of applying patches');
    }
}
