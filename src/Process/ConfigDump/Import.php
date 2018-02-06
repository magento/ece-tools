<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\VersionAwareProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Import implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShellInterface $shell
     * @param MagentoVersion $magentoVersion
     * @param LoggerInterface $logger
     */
    public function __construct(ShellInterface $shell, MagentoVersion $magentoVersion, LoggerInterface $logger)
    {
        $this->shell = $shell;
        $this->magentoVersion = $magentoVersion;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool
    {
        $neededVersion = '2.2';
        if ($this->magentoVersion->isGreaterOrEqual($neededVersion)) {
            return true;
        }
        $this->logger->info("Skipping " . get_class($this) . " because Magento is below version $neededVersion.");
        return false;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->isAvailable()) {
            return;
        }

        $this->shell->execute('php ./bin/magento app:config:import -n');
    }
}
