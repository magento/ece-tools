<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class StaticContentCompressor
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var string
     */
    private static $timeoutCommand = "/usr/bin/timeout -k 30 300 /bin/bash -c ";

    /**
     * @var string
     */
    private static $compressionCommand
        = "find pub/static -type f -size +300c"
        . " '(' -name '*.js' -or -name '*.css' -or -name '*.svg'"
        . " -or -name '*.html' -or -name '*.htm' ')'"
        . " | xargs -n100 -P16 gzip --keep";

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface  $shell
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell
    ) {
        $this->logger        = $logger;
        $this->shell         = $shell;
    }

    /**
     * Compress select files in the static content directory.
     *
     * Compression level 4 takes about as long as compression level 1.
     * It's just as fast because the reduction in I/O from the smaller
     * compressed file speeds up compression about as fast as the increased
     * CPU usage slows it down.
     * Compression level 4 is the default instead of compression level 1 as a
     * result.
     *
     * @param int $compressionLevel
     *
     * @return bool
     */
    public function compressStaticContent(int $compressionLevel = 4): bool
    {
        $compressionCommand = $this->getCompressionCommand($compressionLevel);
        $this->shell->execute($compressionCommand);

        return true;
    }

    /**
     * Get the string containing the shell command for compression.
     *
     * @param int  $compressionLevel
     * @param bool $verbose
     *
     * @return string
     */
    private function getCompressionCommand(
        int $compressionLevel = 1,
        bool $verbose = false
    ): string {

        if (!is_int($compressionLevel)
            || $compressionLevel < 1
            || $compressionLevel > 9
        ) {
            $defaultCompressionLevel = 1;
            $this->logger->info("Compression level was set to \"$compressionLevel\" but this is invalid. Using default compression level of \"$defaultCompressionLevel\".");
            $compressionLevel = $defaultCompressionLevel;
        }

        $compressionCommand = self::$compressionCommand;

        if ($verbose) {
            $compressionCommand .= " -v";
        }

        $compressionCommand .= " -$compressionLevel";

        $compressionCommand
            = self::$timeoutCommand . '"'
            . $compressionCommand . '"';

        return $compressionCommand;
    }
}
