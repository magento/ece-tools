<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\CompressStaticContent;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\Environment;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;


/**
 * Unit test for build-time static content compressor.
 *
 * @package Magento\MagentoCloud\Test\Unit\Process\Build
 */
class CompressStaticContentTest extends TestCase
{
    /**
     * @var CompressStaticContent
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var StaticContentCompressor|Mock
     */
    private $compressorMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->environmentMock = $this->getMockBuilder(Environment::class)->getMock();
        $this->compressorMock = $this->getMockBuilder(StaticContentCompressor::class)->getMock();

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->compressorMock
        );
    }
}
