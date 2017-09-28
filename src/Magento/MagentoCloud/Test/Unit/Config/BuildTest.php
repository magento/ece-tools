<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Build;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $build;

    /**
     * @var Build\Reader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $readerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->readerMock = $this->getMockBuilder(Build\Reader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->build = new Build(
            $this->readerMock
        );
    }

    public function testGet()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'key1' => 'value1',
                'key2' => 'value2',
            ]);

        $this->assertSame('value1', $this->build->get('key1'));
        $this->assertSame('value2', $this->build->get('key2'));
        $this->assertSame(null, $this->build->get('undefined'));
        $this->assertSame('default_val', $this->build->get('undefined', 'default_val'));
    }

    public function testGetVerbosityLevel()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                Build::OPT_VERBOSE_COMMANDS => 'enabled',
            ]);

        $this->assertSame(
            ' -vv ',
            $this->build->getVerbosityLevel()
        );
    }

    public function testGetScdStrategy()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                Build::OPT_SCD_STRATEGY => 'quick',
            ]);

        $this->assertSame(
            '-s quick',
            $this->build->getScdStrategy()
        );
    }
}
