<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy\WarmUp;

use Magento\MagentoCloud\Process\PostDeploy\WarmUp\UrlsPattern;
use Magento\MagentoCloud\Shell\Process;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class UrlsPatternTest extends TestCase
{
    /**
     * @var UrlsPattern
     */
    private $urlsPattern;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);

        $this->urlsPattern = new UrlsPattern(
            $this->loggerMock,
            $this->shellMock
        );
    }

    /**
     * @param string $pattern
     * @param string $command
     * @param array $urlsFromCommand
     * @param array $expectedResult
     * @dataProvider getDataProvider
     */
    public function testGet(string $pattern, string $command, array $urlsFromCommand, array $expectedResult)
    {
        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn(json_encode($urlsFromCommand));
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($processMock);

        $this->assertEquals($expectedResult, array_values($this->urlsPattern->get($pattern)));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [
                'category:*:1',
                'config:show:urls --entity-type="category" --store-id="1"',
                [],
                [],
            ],
            [
                'category:*:*',
                'config:show:urls --entity-type="category"',
                [
                    'http://site1.com/category1',
                    'http://site1.com/category2',
                ],
                [
                    'http://site1.com/category1',
                    'http://site1.com/category2',
                ],
            ],
            [
                'category:/category.*/:*',
                'config:show:urls --entity-type="category"',
                [
                    'http://site1.com/category1',
                    'http://site1.com/category2',
                    'http://site1.com/cat1',
                    'http://site1.com/cat2',
                ],
                [
                    'http://site1.com/category1',
                    'http://site1.com/category2',
                ],
            ],
            [
                'category:cat1:*',
                'config:show:urls --entity-type="category"',
                [
                    'http://site1.com/category1',
                    'http://site1.com/cat1',
                    'http://site1.com/cat2',
                    'http://site2.com/category1',
                    'http://site2.com/cat1',
                    'http://site2.com/cat2',
                ],
                [
                    'http://site1.com/cat1',
                    'http://site2.com/cat1',
                ],
            ],
        ];
    }

    public function testGetWarmUpPatternNotValid()
    {
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Warm-up pattern "wrong:pattern" isn\'t valid.');
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->urlsPattern->get('wrong:pattern');
    }

    public function testGetNotValidJsonReturned()
    {
        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('wrong_json');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:urls --entity-type="category"')
            ->willReturn($processMock);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Can\'t parse result from command config:show:urls'));

        $this->urlsPattern->get('category:*:*');
    }

    /**
     * @param string $pattern
     * @param bool $expected
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(string $pattern, bool $expected)
    {
        $this->assertEquals($expected, $this->urlsPattern->isValid($pattern));
    }

    /**
     * @return array
     */
    public function isValidDataProvider(): array
    {
        return [
            ['test', false],
            ['http://example.com', false],
            ['http://example.com:8000', false],
            ['product:*:*', false],
            ['category:*:store_fr', true],
            ['category:*:1', true],
            ['category:*:*', true],
            ['cms-page:*:1', true],
            ['cms-page:*:*', true],
            ['cms_page:*:*', false],
        ];
    }
}
