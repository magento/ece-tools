<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;

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
    public function __construct(LoggerInterface $logger, ShellInterface $shell, BuildConfig $buildConfig)
    {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->buildConfig = $buildConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.php';

        if (file_exists($configFile)) {
            if (!$this->buildConfig->get(BuildConfig::BUILD_OPT_SKIP_DI_COMPILATION)) {
                $this->logger->info("Running DI compilation");
                $this->shell->execute("php ./bin/magento setup:di:compile {$this->verbosityLevel} ");
            } else {
                $this->logger->info("Skip running DI compilation");
            }
        } else {
            $this->logger->info(
                "Missing config.php, please run the following commands "
                . "\n 1. bin/magento module:enable --all "
                . "\n 2. git add -f app/etc/config.php "
                . "\n 3. git commit -a -m 'adding config.php' "
                . "\n 4. git push"
            );
            exit(6);
        }
    }
}
