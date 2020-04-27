<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Factory\Cache;

/**
 * Validates configuration for Redis Backend model
 */
class RedisBackend implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param MagentoVersion $magentoVersion
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        MagentoVersion $magentoVersion,
        DeployInterface $stageConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->magentoVersion = $magentoVersion;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @return Validator\ResultInterface
     * @throws \Magento\MagentoCloud\Config\ConfigException
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     */
    public function validate(): Validator\ResultInterface
    {
        $redisBackend = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_REDIS_BACKEND);

        if (!in_array($redisBackend, Cache::AVAILABLE_REDIS_BACKEND, true)) {
            return $this->resultFactory->error(
                sprintf(
                    'The variable \'%s\' contains wrong Redis backend model',
                    DeployInterface::VAR_CACHE_REDIS_BACKEND
                ),
                sprintf(
                    'Please, use one of the next Redis backend models: %s',
                    PHP_EOL . implode(PHP_EOL, Cache::AVAILABLE_REDIS_BACKEND)
                )
            );
        }

        if (!$this->magentoVersion->isGreaterOrEqual('2.3.5') && $redisBackend !== Cache::REDIS_BACKEND_CM_CACHE) {
            return $this->resultFactory->error(
                sprintf(
                    'The variable \'%s\' contains wrong configuration.'
                    . 'Your Magento %s does not support the Redis backend model: \'%s\'',
                    DeployInterface::VAR_CACHE_REDIS_BACKEND,
                    $this->magentoVersion->getVersion(),
                    $redisBackend
                ),
                sprintf(
                    'Please use \'%s\' Redis model for your Magento %s',
                    Cache::REDIS_BACKEND_CM_CACHE,
                    $this->magentoVersion->getVersion()
                )
            );
        }

        return $this->resultFactory->success();
    }
}
