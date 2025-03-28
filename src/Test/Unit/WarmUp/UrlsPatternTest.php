<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\WarmUp;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\WarmUp\UrlsPattern;
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
     * @var UrlsPattern\PatternFactory|MockObject
     */
    private $patternFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->patternFactoryMock = $this->createMock(UrlsPattern\PatternFactory::class);

        $this->urlsPattern = new UrlsPattern(
            $this->loggerMock,
            $this->patternFactoryMock
        );
    }

    public function testGetWithGenericException()
    {
        $patternInterfaceMock = $this->getMockForAbstractClass(UrlsPattern\PatternInterface::class);
        $patternInterfaceMock->expects($this->once())
            ->method('getUrls')
            ->with('product', '*', '*')
            ->willThrowException(new GenericException('some error'));
        $this->patternFactoryMock->expects($this->once())
            ->method('create')
            ->with('product')
            ->willReturn($patternInterfaceMock);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('some error');

        $this->urlsPattern->get('product:*:*');
    }

    public function testGetWithShellException()
    {
        $patternInterfaceMock = $this->getMockForAbstractClass(UrlsPattern\PatternInterface::class);
        $patternInterfaceMock->expects($this->once())
            ->method('getUrls')
            ->with('product', '*', '*')
            ->willThrowException(new ShellException('some error'));
        $this->patternFactoryMock->expects($this->once())
            ->method('create')
            ->with('product')
            ->willReturn($patternInterfaceMock);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Command execution failed: some error');

        $this->urlsPattern->get('product:*:*');
    }

    /**
     * @param string $pattern
     * @param array $patternParts
     * @param array $urlsFromPattern
     * @param array $expectedResult
     * @throws \ReflectionException
     * @dataProvider getDataProvider
     */
    public function testGet(string $pattern, array $patternParts, array $urlsFromPattern, array $expectedResult)
    {
        $patternInterfaceMock = $this->getMockForAbstractClass(UrlsPattern\PatternInterface::class);
        $patternInterfaceMock->expects($this->once())
            ->method('getUrls')
            ->with(...$patternParts)
            ->willReturn($urlsFromPattern);

        $this->patternFactoryMock->expects($this->once())
            ->method('create')
            ->with($patternParts[0])
            ->willReturn($patternInterfaceMock);

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
                [
                    'category',
                    '*',
                    '1'
                ],
                [],
                [],
            ],
            [
                'category:/category.*?:*',
                [
                    'category',
                    '/category.*?',
                    '*'
                ],
                [
                    'http://site1.com/category1',
                    'http://site1.com/category2',
                    'http://site1.com/cat1',
                    'http://site1.com/cat1',
                ],
                [
                    'http://site1.com/category1',
                    'http://site1.com/category2',
                    'http://site1.com/cat1',
                ],
            ],
        ];
    }

    public function testGetWarmUpPatternNotValid()
    {
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Warm-up pattern "wrong:pattern" isn\'t valid.');
        $this->patternFactoryMock->expects($this->never())
            ->method('create');

        $this->urlsPattern->get('wrong:pattern');
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
            ['product:*:*', true],
            ['product:sku1|sku2:store1|store 2', true],
            ['category:*:store_fr', true],
            ['category:*:1', true],
            ['category:*:*', true],
            ['cms-page:*:1', true],
            ['cms-page:*:*', true],
            ['cms_page:*:*', false],
            ['store-page:/url/:store1|store2', true],
            ['store_page:/url/:store1|store2', false],
        ];
    }
}
