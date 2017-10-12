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
        if (!$this->environment->isDeployStaticContent()) {
            $this->logger->info(
                "Skipping deploy-time static content compression because isDeployStaticContent() is false."
            );

            return false;
        }

        $startTime = microtime(true);
        $this->staticContentCompressor->compressStaticContent(4);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->logger->info(
            "Static content compression during the deployment phase took $duration seconds."
        );

        return true;
    }
}
