<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
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
     * @var File
     */
    private $file;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     * @param BuildConfig $buildConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        BuildConfig $buildConfig
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
        $this->buildConfig = $buildConfig;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function execute()
    {
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.php';
        $verbosityLevel = $this->buildConfig->getVerbosityLevel();

        if ($this->file->isExists($configFile)) {
            if (!$this->buildConfig->get(BuildConfig::BUILD_OPT_SKIP_DI_COMPILATION)) {
                $this->logger->info('Running DI compilation');
                $this->shell->execute("php ./bin/magento setup:di:compile {$verbosityLevel} ");
            } else {
                $this->logger->info('Skip running DI compilation');
            }
        } else {
            $this->logger->info(
                "Missing config.php, please run the following commands "
                . "\n 1. bin/magento module:enable --all "
                . "\n 2. git add -f app/etc/config.php "
                . "\n 3. git commit -a -m 'adding config.php' "
                . "\n 4. git push"
            );

            throw new \RuntimeException('Missing config.php file', 6);
        }
    }
}
