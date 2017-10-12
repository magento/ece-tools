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
     * @var int
     */
    private static $compressionLevel = 6;

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
    public function execute()
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
            self::$compressionLevel
        );
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->logger->info(
            "Static content compression during the build phase took $duration seconds.",
            [
                'compressionLevel' => self::$compressionLevel,
            ]
        );

        return true;
    }
}
