<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Patch;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection;
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
     * @var GlobalSection
     */
    private $globalSection;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param GlobalSection $globalSection
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        GlobalSection $globalSection
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->globalSection = $globalSection;
    }

    /**
     * Applies all needed patches.
     *
     * @throws ShellException
     * @throws ConfigException
     */
    public function apply(): void
    {
        if ($this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)) {
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
