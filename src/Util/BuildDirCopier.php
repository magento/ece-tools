<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyFactory;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Psr\Log\LoggerInterface;

/**
 * Provides functionality to copy directories on build phase.
 */
class BuildDirCopier
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StrategyFactory
     */
    private $strategyFactory;

    /**
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param StrategyFactory $strategyFactory
     */
    public function __construct(
        LoggerInterface $logger,
        DirectoryList $directoryList,
        StrategyFactory $strategyFactory
    ) {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->strategyFactory = $strategyFactory;
    }

    /**
     * @param string $dir The directory to copy. Pass in its normal location relative to Magento root with no prepending
     * or trailing slashes
     * @param string $strategyName Name of strategy that will be used for copying directories
     *
     * @throws UndefinedPackageException
     */
    public function copy(string $dir, string $strategyName): void
    {
        try {
            $magentoRoot = $this->directoryList->getMagentoRoot();
            $initDir = $this->directoryList->getInit();

            $fromDirectory = $initDir . '/' . $dir;
            $toDirectory = $magentoRoot . '/' . $dir;

            $strategy = $this->strategyFactory->create($strategyName);
            $result = $strategy->copy($fromDirectory, $toDirectory);

            if ($result) {
                $this->logger->debug(
                    sprintf(
                        'Directory %s was copied with strategy: %s',
                        $dir,
                        $strategyName
                    )
                );
            } else {
                $this->logger->warning(
                    sprintf(
                        'Cannot copy directory %s with strategy: %s',
                        $dir,
                        $strategyName
                    ),
                    ['errorCode' => Error::WARN_COPY_MOUNTED_DIRS_FAILED]
                );
            }
        } catch (FileSystemException $e) {
            $this->logger->warning($e->getMessage(), ['errorCode' => Error::WARN_COPY_MOUNTED_DIRS_FAILED]);
        }
    }
}
