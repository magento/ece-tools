<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\ConfigWriter;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Psr\Log\LoggerInterface;

class DbConnection implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @param Environment $environment
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param DeployConfig $deployConfig
     */
    public function __construct(
        Environment $environment,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        DeployConfig $deployConfig
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->deployConfig = $deployConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating env.php DB connection configuration.');

        $config = $this->deployConfig->getConfig();

        $config['db']['connection']['default']['username'] = $this->environment->getDbUser();
        $config['db']['connection']['default']['host'] = $this->environment->getDbHost();
        $config['db']['connection']['default']['dbname'] = $this->environment->getDbName();
        $config['db']['connection']['default']['password'] = $this->environment->getDbPassword();

        $config['db']['connection']['indexer']['username'] = $this->environment->getDbUser();
        $config['db']['connection']['indexer']['host'] = $this->environment->getDbHost();
        $config['db']['connection']['indexer']['dbname'] = $this->environment->getDbName();
        $config['db']['connection']['indexer']['password'] = $this->environment->getDbPassword();

        $this->configWriter->update($config);
    }
}
