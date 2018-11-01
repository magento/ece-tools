<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * {@inheritdoc}
 */
class FixCacheConfig implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

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
     * @param Environment $environment
     * @param ConfigReader $reader
     * @param ConfigWriter $writer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ConfigReader $reader,
        ConfigWriter $writer,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->reader = $reader;
        $this->writer = $writer;
        $this->logger = $logger;
    }

    /**
     * Check if Redis relationship has been removed but Magento cache is configured to use it
     */
    public function execute()
    {
        foreach ($this->environment->getRelationships() as $relationship) {
            if ($relationship[0]['service'] == 'redis') {
                return;
            }
        }

        $env = $this->reader->read();

        if (!isset($env['cache']['frontend'])) {
            return;
        }

        $redisCacheEnabled = false;
        foreach ($env['cache']['frontend'] as $cacheConfig) {
            if ($cacheConfig['backend'] == 'Cm_Cache_Backend_Redis') {
                $redisCacheEnabled = true;
                break;
            }
        }

        if ($redisCacheEnabled) {
            $this->logger->notice(
                'Cache is configured for Redis but no Redis service is available. Temporarily disabling cache.'
            );

            unset($env['cache']);
            $this->writer->create($env);
        }
    }
}
