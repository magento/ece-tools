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
     * @param array $qualityPatches
     * @throws ShellException
     * @throws ConfigException
     */
    public function apply(array $qualityPatches = []): void
    {
        $this->logger->notice('Applying patches');

        $command = 'php ./vendor/bin/ece-patches apply';

        if ($qualityPatches) {
            $command .= sprintf(" '%s'", implode("' '", $qualityPatches));
        }

        if ($this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)) {
            $command .= ' --git-installation 1';
        }

        $command .= ' --no-interaction';

        try {
            $this->shell->execute($command);
        } catch (ShellException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        $this->logger->notice('End of applying patches');
    }
}
