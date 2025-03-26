<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Shell\UtilityException;
use Magento\MagentoCloud\Shell\UtilityManager;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Utility class for static content compression.
 */
class StaticContentCompressor
{
    /**
     * Default gzip compression level if not otherwise specified.
     *
     * Compression level 4 takes about as long as compression level 1.
     * It's just as fast because the reduction in I/O from the smaller
     * compressed file speeds up compression about as fast as the increased
     * CPU usage slows it down.
     * Compression level 4 is the default instead of compression level 1 as a
     * result.
     */
    private const DEFAULT_COMPRESSION_LEVEL = 4;

    /**
     * Default timeout time in seconds for process static content compression.
     */
    private const DEFAULT_COMPRESSION_TIMEOUT = 600;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var UtilityManager
     */
    private $utilityManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param UtilityManager $utilityManager
     */
    public function __construct(
        DirectoryList $directoryList,
        LoggerInterface $logger,
        ShellInterface $shell,
        UtilityManager $utilityManager
    ) {
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->utilityManager = $utilityManager;
    }

    /**
     * Compress select files in the static content directory.
     *
     * @param int $compressionLevel
     * @param int $timeout
     * @param string $verbose
     * @return void
     * @throws ShellException
     * @throws UndefinedPackageException
     * @throws UtilityException
     */
    public function process(
        int $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL,
        int $timeout = self::DEFAULT_COMPRESSION_TIMEOUT,
        string $verbose = ''
    ) {
        if ($compressionLevel === 0) {
            $this->logger->info('Static content compression was disabled.');

            return;
        }

        $compressionCommand = $this->getCompressionCommand($compressionLevel, $timeout);

        $startTime = microtime(true);
        $this->shell->execute($compressionCommand);
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        if ($verbose) {
            $this->logger->info(
                "Static content compression took $duration seconds.",
                [
                    'commandRun' => $compressionCommand,
                ]
            );
        }
    }

    /**
     * Return the inner find/xargs/gzip command that compresses the content.
     * Ignores any of the directories that are deleting in the background.
     *
     * @param int $compressionLevel
     * @return string
     *
     * @throws UndefinedPackageException
     */
    private function innerCompressionCommand(int $compressionLevel): string
    {
        return sprintf(
            "find %s -type d -name %s -prune -o -type f -size +300c"
            . " '(' -name '*.js' -or -name '*.css' -or -name '*.svg'"
            . " -or -name '*.html' -or -name '*.htm' ')' -print0"
            . " | xargs -0 -n100 -P16 gzip -q --keep -%d",
            escapeshellarg($this->directoryList->getPath(DirectoryList::DIR_STATIC)),
            escapeshellarg(File::DELETING_PREFIX . '*'),
            $compressionLevel
        );
    }

    /**
     * Get the string containing the full shell command for compression.
     *
     * @param int $compressionLevel
     * @param int $timeout
     * @return string
     *
     * @throws UndefinedPackageException
     * @throws UtilityException
     */
    private function getCompressionCommand(
        int $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL,
        int $timeout = self::DEFAULT_COMPRESSION_TIMEOUT
    ): string {
        $compressionLevel = $compressionLevel > 0 && $compressionLevel <= 9
            ? $compressionLevel
            : self::DEFAULT_COMPRESSION_LEVEL;

        return sprintf(
            '%s -k 30 %s %s -c %s',
            $this->utilityManager->get(UtilityManager::UTILITY_TIMEOUT),
            $timeout,
            $this->utilityManager->get(UtilityManager::UTILITY_SHELL),
            escapeshellarg($this->innerCompressionCommand($compressionLevel))
        );
    }
}
