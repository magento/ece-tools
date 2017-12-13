
<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
/**
 * @inheritdoc
 */
class CompileDi implements ProcessInterface
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
     * @var BuildConfig
     */
    private $buildConfig;
    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param BuildConfig $buildConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        BuildConfig $buildConfig
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->buildConfig = $buildConfig;
    }
    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function execute()
    {
        $verbosityLevel = $this->buildConfig->getVerbosityLevel();
        $this->logger->info('Running DI compilation');
        $this->shell->execute("php ./bin/magento setup:di:compile {$verbosityLevel}");
    }
}