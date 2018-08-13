<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\VersionAwareProcessInterface;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Import implements ProcessInterface
{
    /**
     * @var ExecBinMagento
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
     * @param ExecBinMagento $shell
     * @param MagentoVersion $magentoVersion
     * @param LoggerInterface $logger
     */
    public function __construct(ExecBinMagento $shell, MagentoVersion $magentoVersion, LoggerInterface $logger)
    {
        $this->shell = $shell;
        $this->magentoVersion = $magentoVersion;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            return;
        }

        $this->shell->execute('app:config:import');
    }
}
