<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * {@inheritdoc}
 */
class FixCacheConfig implements ProcessInterface
{
    /**
     * @var ConfigReader
     */
    private $reader;

    /**
     * @var ConfigWriter
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConfigReader $reader
     * @param ConfigWriter $writer
     * @param LoggerInterface $logger
     */
    public function __construct(ConfigReader $reader, ConfigWriter $writer, LoggerInterface $logger)
    {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->logger = $logger;
    }

    /**
     * Check if Redis relationship has been removed but Magento cache is configured to use it
     */
    public function execute()
    {
        $env = $this->reader->read();

        if (!isset($env['cache']['frontend'])) {
            return;
        }

        $configurationValid = true;
        foreach ($env['cache']['frontend'] as $cacheConfig) {
            if ($cacheConfig['backend'] == 'Cm_Cache_Backend_Redis' &&
                !$this->testRedisConnection($cacheConfig['backend_options'])
            ) {
                $configurationValid = false;
                break;
            }
        }

        if (!$configurationValid) {
            $this->logger->notice(
                'Cache is configured for a Redis service that is not available. Temporarily disabling cache.'
            );

            unset($env['cache']);
            $this->writer->create($env);
        }
    }

    /**
     * Test if a socket connection can be opened to defined backend.
     *
     * @param array $backendOptions
     *
     * @return bool
     */
    private function testRedisConnection(array $backendOptions): bool
    {
        extract($backendOptions);

        if (!isset($server) || !isset($port)) {
            throw new ProcessException('Missing required Redis configuration!');
        }

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $connected = @socket_connect($sock, $server, $port);
        socket_close($sock);

        return $connected;
    }
}
