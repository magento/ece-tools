<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;

/**
 * @inheritdoc
 */
class CompressStaticContent implements ProcessInterface
{
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
     * Compression level to be used by gzip.
     *
     * Compression level 4 is just as fast as level 1 on modern processors due to reduced filesystem I/O.
     * Level 4 is appropriate for when we must finish compression as fast as possible, such as in this site
     * deploy phase that brings the site down.
     *
     * @var int
     */
    private static $compressionLevel = 4;

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
     * @return bool
     */
    public function execute(): bool
    {
        // Only proceed if static content deployment has already run.
        if (!$this->environment->isDeployStaticContent()) {
            $this->logger->info(
                "Skipping deploy-time static content compression because isDeployStaticContent() is false."
            );

            return false;
        }

        $startTime = microtime(true);
        $this->staticContentCompressor->compressStaticContent(
            self::$compressionLevel
        );
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->logger->info(
            "Static content compression during the deployment phase took $duration seconds.",
            [
                'compressionLevel' => self::$compressionLevel,
            ]
        );

        return true;
    }
}
