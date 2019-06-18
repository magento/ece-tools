<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

class Redis implements ServiceInterface
{
    const RELATIONSHIP_KEY = 'redis';
    const RELATIONSHIP_SLAVE_KEY = 'redis-slave';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Environment $environment
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ShellInterface $shell,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->shell = $shell;
        $this->logger = $logger;
    }
    /**
     * @inheritdoc
     */
    public function isInstalled(): bool
    {
        return (bool)$this->getConfiguration();
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_KEY)[0] ?? [];
    }

    /**
     * Returns service configuration for slave.
     *
     * @return array
     */
    public function getSlaveConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_SLAVE_KEY)[0] ?? [];
    }

    /**
     * Returns version of the service.
     *
     * @return string
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            try {
                $this->version = '0';

                $redisConfig = $this->getConfiguration();
                if (!$redisConfig) {
                    return $this->version;
                }

                if (isset($redisConfig['type']) && strpos(':', $redisConfig['type']) !== false) {
                    $this->version = explode(':', $redisConfig['type'])[1];

                    return $this->version;
                }

                $cmd = 'redis-cli';
                $cmd .= isset($redisConfig['server']) ? ' -h ' . $redisConfig['server'] : '';
                $cmd .= isset($redisConfig['port']) ? ' -p ' . $redisConfig['port'] : '';
                $cmd .= isset($redisConfig['database']) ? ' -n ' . $redisConfig['database'] : '';

                $process = $this->shell->execute($cmd . ' INFO server | grep "redis_version"');

                $this->version = str_replace('redis_version:', '', $process->getOutput());
            } catch (ShellException $exception) {
                $this->logger->warning('Can\'t get version of redis: ' . $exception->getMessage());
            }
        }

        return $this->version;
    }
}
