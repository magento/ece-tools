<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ApplyPatches implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     */
    public function __construct(ShellInterface $shell, LoggerInterface $logger)
    {
        $this->shell = $shell;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Applying patches.');
        $this->shell->execute('php vendor/bin/m2-apply-patches');
    }
}
