<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * Returns Redis service configurations.
 */
class Redis implements ServiceInterface
{
    const RELATIONSHIP_KEY = 'redis';
    const RELATIONSHIP_SLAVE_KEY = 'redis-slave';

    /**
     * @var Environment
     */
    private $environment;

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
     */
    public function __construct(
        Environment $environment,
        ShellInterface $shell
    ) {
        $this->environment = $environment;
        $this->shell = $shell;
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
     * Retrieves Redis service version whether from relationship configuration
     * or using CLI command (for PRO environments)
     *
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';
            $redisConfig = $this->getConfiguration();

            //on integration environments
            if (isset($redisConfig['type']) && strpos($redisConfig['type'], ':') !== false) {
                $this->version = explode(':', $redisConfig['type'])[1];
            } elseif (isset($redisConfig['host']) && isset($redisConfig['port'])) {
                //on dedicated environments
                try {
                    $process = $this->shell->execute(
                        sprintf(
                            'redis-cli -p %s -h %s info | grep redis_version',
                            $redisConfig['port'],
                            $redisConfig['host']
                        )
                    );
                    preg_match('/^(?:redis_version:)(\d+\.\d+)/', $process->getOutput(), $matches);
                    $this->version = $matches[1] ?? '0';
                } catch (ShellException $exception) {
                    throw new ServiceException($exception->getMessage());
                }
            }
        }

        return $this->version;
    }
}
