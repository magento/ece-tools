<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class StaticContentCompressorTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * Set up the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock  = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();

        $this->staticContentCompressor = new StaticContentCompressor(
            $this->loggerMock,
            $this->shellMock
        );
    }


    /**
     * Generate an unused filename in the target folder.
     *
     * @param string $folder
     * @param string $extension
     *
     * @return string
     */
    private function newFilename($folder = '.', $extension = 'html')
    {
        // Generate a random, unused filename.
        $alphabet     = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $testFilename = null;
        while (!$testFilename) {
            $tempFilename = '';

            for ($i = 0; $i <= 16; ++$i) {
                // Append a random character from the alphabet string.
                $tempFilename .= $alphabet[rand(0, count($alphabet) - 1)];
            }
            $tempFilename = 'DUMMY_' . $tempFilename . '.' . $extension;

            if (file_exists($folder . '/' . $tempFilename)) {
                continue;
            }

            $testFilename = $tempFilename;
        }

        return $testFilename;
    }

    /**
     * Test the method that compresses the files.
     */
    public function testCompression()
    {
        $targetDir = $this->staticContentCompressor::TARGET_DIR;

        // If the path to the target directory exists but it isn't a directory, there's a big problem with the user's
        // directory structure and we can't continue.
        $this->assertFalse(file_exists($targetDir) && !is_dir($targetDir));

        // Make the pub/static directory if it doesn't already exist.
        $madeDir = false;
        if (!file_exists($targetDir)) {
            $madeDir = true;
            mkdir($targetDir, 0777, true);
        }

        // Get a filename for the new test file and create it.
        $testFileUncompressed = $this->newFilename($targetDir);
        copy(__FILE__, "$targetDir/$testFileUncompressed");

        // See if the test file gets compressed by the compressor.
        $this->staticContentCompressor->compressStaticContent();
        print_r(scandir($targetDir));

        // Delete the test file we just made.
        unlink("$targetDir/$testFileUncompressed");

        // Remove both pub and static folders if we just made them and they're completely empty.
        if ($madeDir && count(scandir(dirname($targetDir))) <= 3 && count(scandir($targetDir)) <= 3) {
            rmdir($targetDir);
            rmdir(dirname($targetDir));
        }
    }
}
