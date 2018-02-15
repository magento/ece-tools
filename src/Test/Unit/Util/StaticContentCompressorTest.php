<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Shell\UtilityManager;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Unit test for static content compression.
 */
class StaticContentCompressorTest extends TestCase
{
    /**
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var UtilityManager|Mock
     */
    private $utilityManagerMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->utilityManagerMock = $this->createMock(UtilityManager::class);

        $this->staticContentCompressor = new StaticContentCompressor(
            $this->loggerMock,
            $this->shellMock,
            $this->utilityManagerMock
        );
    }

    /**
     * @param int $compressionLevel
     * @dataProvider compressionDataProvider
     */
    public function testCompression(int $compressionLevel)
    {
        $expectedCommand = '/usr/bin/timeout -k 30 600 /bin/bash -c "find ';

        $this->shellMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->logicalAnd(
                $this->stringContains($expectedCommand),
                $this->stringContains(" -{$compressionLevel}")
            ));
        $this->utilityManagerMock->method('has')
            ->willReturn(true);
        $this->utilityManagerMock->method('get')
            ->willReturnMap([
                [UtilityManager::UTILITY_TIMEOUT, '/usr/bin/timeout'],
                [UtilityManager::UTILITY_BASH, '/bin/bash'],
            ]);

        $this->staticContentCompressor->process($compressionLevel);
    }

    /**
     * @return array
     */
    public function compressionDataProvider(): array
    {
        return [
            [4],
        ];
    }

    public function testCompressionDisabled()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content compression was disabled.');

        $this->staticContentCompressor->process(0);
    }

    public function testUtilityNotFound()
    {
        $this->utilityManagerMock->expects($this->once())
            ->method('has')
            ->with(UtilityManager::UTILITY_TIMEOUT)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Timeout utility not found in the system.');

        $this->staticContentCompressor->process(1);
    }
}
