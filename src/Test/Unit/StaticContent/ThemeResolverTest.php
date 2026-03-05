<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\StaticContent\ThemeResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test class for ThemeResolver
 */
#[AllowMockObjectsWithoutExpectations]
class ThemeResolverTest extends TestCase
{
    /**
     * @var ThemeResolver|MockObject
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
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->themeResolver = $this->getMockBuilder(ThemeResolver::class)
            ->onlyMethods(['getThemes'])
            ->setConstructorArgs([$this->loggerMock])
            ->getMock();
    }

    /**
     * Test resolve method.
     *
     * @param string $expectedReturn
     * @param  string $passedTheme
     * @dataProvider resolveDataProvider
     * @return void
     * @throws \ReflectionException
     */
    #[DataProvider('resolveDataProvider')]
    public function testResolve(string $expectedReturn, string $passedTheme): void
    {
        $this->themeResolver->method('getThemes')
            ->willReturn(['SomeVendor/sometheme']);

        $messages = [];

        $this->loggerMock->method('warning')
            ->willReturnCallback(
                function ($msg) use (&$messages) {
                    $messages[] = $msg;
                }
            );

        $this->loggerMock->expects($this->never())
            ->method('error');

        $this->assertEquals(
            $expectedReturn,
            $this->themeResolver->resolve($passedTheme)
        );

        $this->assertCount(2, $messages);
        $this->assertSame(
            'Theme ' . $passedTheme . ' does not exist, attempting to resolve.',
            $messages[0]
        );
        $this->assertSame(
            'Theme found as SomeVendor/sometheme.  Using corrected name instead.',
            $messages[1]
        );
    }

    /**
     * Data provider for resolve method.
     *
     * @return array
     */
    public static function resolveDataProvider(): array
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

    /**
     * Test correct method.
     *
     * @return void
     */
    public function testCorrect(): void
    {
        $this->themeResolver->method('getThemes')
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

    /**
     * Test no resolve method.
     *
     * @return void
     */
    public function testNoResolve(): void
    {
        $this->themeResolver->method('getThemes')
            ->willReturn(['SomeVendor/sometheme']);

        $warnings = [];
        $errors = [];

        $this->loggerMock->method('warning')
            ->willReturnCallback(
                function ($msg) use (&$warnings) {
                    $warnings[] = $msg;
                }
            );

        $this->loggerMock->method('error')
            ->willReturnCallback(
                function ($msg) use (&$errors) {
                    $errors[] = $msg;
                }
            );

        $this->assertEquals(
            '',
            $this->themeResolver->resolve('SomeVendor/doesntExist')
        );

        $this->assertCount(1, $warnings);
        $this->assertSame('Theme SomeVendor/doesntExist does not exist, attempting to resolve.', $warnings[0]);

        $this->assertCount(1, $errors);
        $this->assertSame('Unable to resolve theme.', $errors[0]);
    }
}
