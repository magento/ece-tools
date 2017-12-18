<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FlagFile\StaticContentDeployPendingFlag;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;

/**
 * Compress static content at deploy time.
 */
class CompressStaticContent implements ProcessInterface
{
    /**
     * Compression level to be used by gzip.
     *
     * This should be an integer between 1 and 9, inclusive.
     * Compression level 4 is just as fast as level 1 on modern processors due to reduced filesystem I/O.
     * Level 4 is appropriate for when we must finish compression as fast as possible, such as in this site
     * deploy phase that brings the site down.
     */
    const COMPRESSION_LEVEL = 4;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * @var FlagFilePool
     */
    private $flagFilePool;

    /**
     * CompressStaticContent constructor.
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param StaticContentCompressor $staticContentCompressor
     * @param FlagFilePool $flagFilePool
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        StaticContentCompressor $staticContentCompressor,
        FlagFilePool $flagFilePool
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->staticContentCompressor = $staticContentCompressor;
        $this->flagFilePool = $flagFilePool;
    }

    /**
     * Execute the deploy-time static content compression process.
     *
     * @return void
     */
    public function execute()
    {
        if ($this->environment->isDeployStaticContent()) {
            if ($this->flagFilePool->getFlag(StaticContentDeployPendingFlag::KEY)->exists()) {
                $this->logger->info('Postpone static content compression until prestart');
                return;
            }
            $this->staticContentCompressor->process(
                $this->environment->getVariable(
                    Environment::VAR_SCD_COMPRESSION_LEVEL,
                    static::COMPRESSION_LEVEL
                ),
                $this->environment->getVerbosityLevel()
            );
        } else {
            $this->logger->info(
                "Static content deployment was performed during the build phase or disabled. Skipping deploy phase"
                . " static content compression."
            );
        }
    }
}
