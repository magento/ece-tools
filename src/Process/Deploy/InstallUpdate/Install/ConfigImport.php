<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use Psr\Log\LoggerInterface;

/**
 * Imports configurations after changes env.php
 *
 * {@inheritdoc}
 */
class ConfigImport implements ProcessInterface
{
    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ShellFactory $shellFactory
     * @param LoggerInterface $logger
     * @param MagentoVersion $version
     */
    public function __construct(
        ShellFactory $shellFactory,
        LoggerInterface $logger,
        MagentoVersion $version
    ) {
        $this->magentoShell = $shellFactory->createMagento();
        $this->logger = $logger;
        $this->magentoVersion = $version;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            return;
        }

        $this->logger->info('Run app:config:import command');
        $this->magentoShell->execute('app:config:import');
    }
}
