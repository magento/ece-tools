<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\Stage\BuildInterface;

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
     * @var StaticContentCompressor
     */
    private $compressor;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * CompressStaticContent constructor.
     *
     * @param LoggerInterface $logger
     * @param StaticContentCompressor $compressor
     * @param FlagManager $flagManager
     * @param BuildInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        StaticContentCompressor $compressor,
        FlagManager $flagManager,
        BuildInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->compressor = $compressor;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
    }

    /**
     * Execute the build-time static content compression process.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)) {
            $this->compressor->process(
                $this->stageConfig->get(BuildInterface::VAR_SCD_COMPRESSION_LEVEL),
                $this->stageConfig->get(BuildInterface::VAR_SCD_COMPRESSION_TIMEOUT),
                $this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS)
            );
        } else {
            $this->logger->info(
                'Skipping build-time static content compression because static content deployment has not happened.'
            );

            return;
        }
    }
}
