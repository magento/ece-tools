<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service\Redis;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Shell\ShellException;

/**
 * Returns Redis version
 */
class Version
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param ShellInterface $shell
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;
    }

    /**
     * Retrieves Redis service version whether from relationship configuration
     * or using CLI command (for PRO environments)
     *
     * @param array $redisConfig
     * @return string
     * @throws ServiceException
     */
    public function getVersion(array $redisConfig): string
    {
        $version = '0';

        // On integration environments
        if (isset($redisConfig['type']) && strpos($redisConfig['type'], ':') !== false) {
            $version = explode(':', $redisConfig['type'])[1];
        } elseif (isset($redisConfig['host'], $redisConfig['port'])) {
            // On dedicated environments
            $cmd = sprintf('redis-cli -p %s -h %s', (string)$redisConfig['port'], (string)$redisConfig['host']);

            if (!empty($redisConfig['password'])) {
                $cmd .= ' -a ' . $redisConfig['password'];
            }

            try {
                $process = $this->shell->execute($cmd .' info | grep redis_version');

                preg_match('/^(?:redis_version:)(\d+\.\d+)/', $process->getOutput(), $matches);
                $version = $matches[1] ?? '0';
            } catch (ShellException $exception) {
                throw new ServiceException($exception->getMessage());
            }
        }

        return $version;
    }
}
