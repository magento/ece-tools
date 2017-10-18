<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
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
     * @var Environment
     */
    private $environment;

    /**
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * @param LoggerInterface         $logger
     * @param Environment             $environment
     * @param StaticContentCompressor $staticContentCompressor
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        StaticContentCompressor $staticContentCompressor
    ) {
        $this->logger                  = $logger;
        $this->environment             = $environment;
        $this->staticContentCompressor = $staticContentCompressor;
    }

    /**
     * Execute the build-time static content compression process.
     *
     * @return bool
     */
    public function execute(): bool
    {
        // Only proceed if static content deployment has already run.
        if (!$this->environment->isStaticDeployInBuild()) {
            $this->logger->info(
                "Skipping build-time static content compression because static content deployment hasn't happened."
            );

            return false;
        }

        $startTime = microtime(true);
        $this->staticContentCompressor->compressStaticContent(
            static::COMPRESSION_LEVEL
        );
        $endTime = microtime(true);

        $commandRun = $this->staticContentCompressor->getLastShellCommand();
        $duration = $endTime - $startTime;
        $this->logger->info(
            "Static content compression during the build phase took $duration seconds.",
            [
                'commandRun' => $commandRun
            ]
        );

        return true;
    }
}
