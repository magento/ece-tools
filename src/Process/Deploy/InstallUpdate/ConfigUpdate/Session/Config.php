<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Session;

use Composer\Semver\Comparator;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Service\Redis;

/**
 * Returns session configuration.
 */
class Config
{
    /**
     * Redis database to store session data
     */
    const REDIS_DATABASE_SESSION = 0;

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Comparator
     */
    private $comparator;

    /**
     * @param Redis $redis
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     * @param Manager $manager
     * @param Comparator $comparator
     */
    public function __construct(
        Redis $redis,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        Manager $manager,
        Comparator $comparator
    ) {
        $this->redis = $redis;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->manager = $manager;
        $this->comparator = $comparator;
    }

    /**
     * Returns session configuration.
     *
     * If session configuration sets in SESSION_CONFIGURATION variable without _merge option return it,
     * otherwise checks if exists redis configuration in relationships and if so, makes session configuration for redis.
     * Merge configuration from env variable is merging enabled.
     * Returns an empty array in other case.
     *
     * @return array
     */
    public function get(): array
    {
        $envSessionConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_SESSION_CONFIGURATION);

        if (!$this->configMerger->isEmpty($envSessionConfiguration)
            && !$this->configMerger->isMergeRequired($envSessionConfiguration)
        ) {
            return $envSessionConfiguration;
        }

        $redisConfig = $this->redis->getConfiguration();

        if (!$redisConfig) {
            return [];
        }

        $defaultConfig = [
            'save' => 'redis',
            'redis' => [
                'host' => $redisConfig['host'],
                'port' => $redisConfig['port'],
                'database' => self::REDIS_DATABASE_SESSION,
            ],
        ];

        $disableLocking = $this->resolveDefaultDisableLocking();

        if (null !== $disableLocking) {
            $defaultConfig['redis']['disable_locking'] = $disableLocking;
        }

        return $this->configMerger->mergeConfigs($defaultConfig, $envSessionConfiguration);
    }

    /**
     * This to correctly handle inverted value in `disable_locking` parameter.
     *
     * @return int|null
     * @link https://github.com/colinmollenhour/php-redis-session-abstract/commit/6f005b2c3755e4a96ddad821e2ea15d66fb314ae
     */
    private function resolveDefaultDisableLocking()
    {
        try {
            $package = $this->manager->get('colinmollenhour/php-redis-session-abstract');
        } catch (\Exception $exception) {
            return null;
        }

        if ($this->comparator::greaterThanOrEqualTo($package->getVersion(), '1.3.4')) {
            return 1;
        }

        return 0;
    }
}
