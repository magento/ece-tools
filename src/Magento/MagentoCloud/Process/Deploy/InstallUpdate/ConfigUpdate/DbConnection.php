<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
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
     * @param Environment $environment
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ConfigWriter $configWriter,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating env.php DB connection configuration.');

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
