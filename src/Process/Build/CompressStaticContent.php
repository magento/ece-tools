<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\FlagFile\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager as FlagFileManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;

/**
 * Compress static content at build time.
 */
class CompressStaticContent implements ProcessInterface
{
    /**
     * Compression level to be used by gzip.
     *
     * This should be an integer between 1 and 9, inclusive.
     * Compression level 6 is the best trade-off between speed and compression strength.
     * Level 6 obtains 99% of the compression ratio that level 9 does in 45% of the time.
     * Level 6 is appropriate for when we can afford to wait a few extra seconds for better compression,
     * such as in this site build phase.
     */
    const COMPRESSION_LEVEL = 6;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * @var FlagFileManager
     */
    private $flagFileManager;

    /**
     * CompressStaticContent constructor.
     * @param LoggerInterface $logger
     * @param BuildConfig $buildConfig
     * @param StaticContentCompressor $staticContentCompressor
     * @param FlagFileManager $flagFileManager
     */
    public function __construct(
        LoggerInterface $logger,
        BuildConfig $buildConfig,
        StaticContentCompressor $staticContentCompressor,
        FlagFileManager $flagFileManager
    ) {
        $this->logger = $logger;
        $this->buildConfig = $buildConfig;
        $this->staticContentCompressor = $staticContentCompressor;
        $this->flagFileManager = $flagFileManager;
    }

    /**
     * Execute the build-time static content compression process.
     *
     * @return void
     */
    public function execute()
    {
        if ($this->flagFileManager->exists(StaticContentDeployInBuild::KEY)) {
            $this->staticContentCompressor->process(
                $this->buildConfig->get(BuildConfig::OPT_SCD_COMPRESSION_LEVEL, static::COMPRESSION_LEVEL),
                $this->buildConfig->getVerbosityLevel()
            );
        } else {
            $this->logger->info(
                "Skipping build-time static content compression because static content deployment hasn't happened."
            );

            return;
        }
    }
}
