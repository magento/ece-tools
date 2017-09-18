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

class Amqp implements ProcessInterface
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
        $mqConfig = $this->environment->getRelationship('mq');
        $config = $this->deployConfig->getConfig();

        if (count($mqConfig)) {
            $this->logger->info('Updating env.php AMQP configuration.');
            $amqpConfig = $mqConfig[0];
            $config['queue']['amqp']['host'] = $amqpConfig['host'];
            $config['queue']['amqp']['port'] = $amqpConfig['port'];
            $config['queue']['amqp']['user'] = $amqpConfig['username'];
            $config['queue']['amqp']['password'] = $amqpConfig['password'];
            $config['queue']['amqp']['virtualhost'] = '/';
            $config['queue']['amqp']['ssl'] = '';
        } else {
            $config = $this->removeAmqpConfig($config);
        }

        $this->configWriter->update($config);
    }

    /**
     * Remove AMQP configuration from env.php
     *
     * @param array $config
     * @return array
     */
    private function removeAmqpConfig(array $config)
    {
        $this->logger->info('Removing AMQP configuration from env.php.');

        if (isset($config['queue']['amqp'])) {
            if (count($config['queue']) > 1) {
                unset($config['queue']['amqp']);
            } else {
                unset($config['queue']);
            }
        }

        return $config;
    }
}
