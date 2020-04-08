<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\StaticContent\ThemeResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ThemeResolverTest extends TestCase
{
    /**
     * @var ThemeResolver
     */
    private $themeResolver;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->themeResolver = $this->getMockBuilder(ThemeResolver::class)
            ->setMethods(['getThemes'])
            ->setConstructorArgs([
                $this->loggerMock,
            ])->getMock();
    }

    /**
     * @param string $expectedReturn
     * @param string $passedTheme
     *
     * @dataProvider testResolveDataProvider
     */
    public function testResolve(string $expectedReturn, string $passedTheme): void
    {
        $this->themeResolver->expects($this->once())
            ->method('getThemes')
            ->willReturn(['SomeVendor/sometheme']);

        $this->loggerMock->expects($this->exactly(2))
            ->method('warning')
            ->willReturnOnConsecutiveCalls(
                'Theme SomeVendor/Sometheme does not exist, attempting to resolve.',
                'Theme found as SomeVendor/sometheme Using corrected name instead'
            );

        $this->assertEquals(
            $expectedReturn,
            $this->themeResolver->resolve($passedTheme)
        );
    }

    public function testResolveDataProvider(): array
    {
        return [
            'Incorrect Theme' => [
                'expectedReturn' => 'SomeVendor/sometheme',
                'passedTheme' => 'SomeVendor/Sometheme',
            ],
            'Incorrect Vendor' => [
                'expectedReturn' => 'SomeVendor/sometheme',
                'passedTheme' => 'somevendor/sometheme',
            ],
        ];
    }

    public function testCorrect(): void
    {
        $this->themeResolver->expects($this->once())
            ->method('getThemes')
            ->willReturn(['SomeVendor/sometheme']);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->loggerMock->expects($this->never())
            ->method('error');

        $this->assertEquals(
            'SomeVendor/sometheme',
            $this->themeResolver->resolve('SomeVendor/sometheme')
        );
    }

    public function testNoResolve(): void
    {
        $this->themeResolver->expects($this->once())
            ->method('getThemes')
            ->willReturn(['SomeVendor/sometheme']);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->willReturn('Theme SomeVendor/doesntExist does not exist, attempting to resolve.');
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->willReturn('Unable to resolve theme.');

        $this->assertEquals(
            '',
            $this->themeResolver->resolve('SomeVendor/doesntExist')
        );
    }
}
