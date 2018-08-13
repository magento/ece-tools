<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\VersionAwareProcessInterface;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Psr\Log\LoggerInterface;

/**
 * Imports configurations after changes env.php
 *
 * {@inheritdoc}
 */
class ConfigImport implements ProcessInterface
{
    /**
     * @var ExecBinMagento
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ExecBinMagento $shell
     * @param LoggerInterface $logger
     * @param MagentoVersion $version
     */
    public function __construct(
        ExecBinMagento $shell,
        LoggerInterface $logger,
        MagentoVersion $version
    ) {
        $this->shell = $shell;
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
        $this->shell->execute('app:config:import');
    }
}
