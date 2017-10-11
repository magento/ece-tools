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
                "Skipping build-time static content compression "
                . "because static content deployment hasn't happened yet.");

            return false;
        }

        $startTime = microtime(true);
        $this->staticContentCompressor->compressStaticContent(6);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->logger->info(
            "Static content compression took $duration seconds.");

        return true;
    }
}
