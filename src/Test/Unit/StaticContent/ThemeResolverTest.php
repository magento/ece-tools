<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\StaticContent\ThemeResolver;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use phpmock\phpunit\PHPMock;

/**
 * @inheritdoc
 */
class ThemeResolverTest extends TestCase
{
    use PHPMock;
    /**
     * @var ThemeResolver
     */
    private $themeResolver;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var FileList|Mock
     */
    private $fileMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->themeResolver = $this->getMockBuilder(ThemeResolver::class)
            ->setMethods(array('getThemes'))
            ->setConstructorArgs([
                $this->loggerMock,
                $this->fileMock,
                $this->directoryListMock,
                ])
            ->getMock();
    }

    /**
     * @dataProvider testResolveDataProvider
     */
    public function testResolve(string $expectedReturn, string $passedTheme)
    {
        $this->themeResolver->expects($this->once())
            ->method('getThemes')
            ->willReturn(['SomeVendor/sometheme']);

        $this->loggerMock->expects($this->exactly(2))
            ->method('warning')
            ->willReturnOnConsecutiveCalls(
                'Theme SomeVendor/Sometheme does not exist.',
                'Theme found as SomeVendor/sometheme Using corrected name instead'
            );

        $this->assertEquals(
            $expectedReturn,
            $this->themeResolver->resolve($passedTheme)
        );
    }

    public function testResolveDataProvider()
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

    public function testNoResolve()
    {
        $this->themeResolver->expects($this->once())
            ->method('getThemes')
            ->willReturn(['SomeVendor/sometheme']);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->willReturn('Theme SomeVendor/doesntExist does not exist.');

        $this->assertEquals(
            '',
            $this->themeResolver->resolve('SomeVendor/doesntExist')
        );
    }
}
