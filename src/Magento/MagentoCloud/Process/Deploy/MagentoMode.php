<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

class MagentoMode implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @param Environment $env
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     * @param DeployConfig $deployConfig
     */
    public function __construct(
        Environment $env,
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        DeployConfig $deployConfig
    ) {
        $this->env = $env;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
        $this->deployConfig = $deployConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoApplicationMode = $this->deployConfig->getApplicationMode();
        $this->logger->info("Set Magento application mode to '{$magentoApplicationMode}'");

        /* Enable application mode */
        if ($magentoApplicationMode == DeployConfig::MAGENTO_PRODUCTION_MODE) {
            $this->logger->info("Enable production mode");
            $configFileName = $this->env->getConfigFilePath();
            $config = include $configFileName;
            $config['MAGE_MODE'] = 'production';
            $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';
            $this->file->filePutContents($configFileName, $updatedConfig);
        } else {
            $this->logger->info("Enable developer mode");
            $this->shell->execute(
                "php ./bin/magento deploy:mode:set " . DeployConfig::MAGENTO_DEVELOPER_MODE . $this->deployConfig->getVerbosityLevel()
            );
        }
    }
}
