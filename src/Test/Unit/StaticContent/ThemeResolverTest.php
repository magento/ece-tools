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

        $this->themeResolver = new ThemeResolver(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @dataProvider testResolveDataProvider
     */
    public function testResolve(string $expectedReturn, string $passedTheme)
    {
        $testRegistration=$this->getTestRegistration();

        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->withConsecutive(
                [DirectoryList::DIR_DESIGN, true],
                [DirectoryList::DIR_VENDOR, true]
            )
            ->willReturnOnConsecutiveCalls(
                'app/design',
                'vendor'
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('glob')
            ->withConsecutive(
                ['app/design/*/*/*/theme.xml'],
                ['vendor/*/*/theme.xml']
            )
            ->willReturnOnConsecutiveCalls(
                ['app/design/frontend/SomeVendor/sometheme/theme.xml'],
                ['vendor/SomeVendor/theme-frontend-sometheme/theme.xml']
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->withConsecutive(
                ['app/design/frontend/SomeVendor/sometheme/registration.php'],
                ['vendor/SomeVendor/theme-frontend-sometheme/registration.php']
            )
            ->willReturn($testRegistration);
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
        $testRegistration=$this->getTestRegistration();

        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->withConsecutive(
                [DirectoryList::DIR_DESIGN, true],
                [DirectoryList::DIR_VENDOR, true]
            )
            ->willReturnOnConsecutiveCalls(
                'app/design',
                'vendor'
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('glob')
            ->withConsecutive(
                ['app/design/*/*/*/theme.xml'],
                ['vendor/*/*/theme.xml']
            )
            ->willReturnOnConsecutiveCalls(
                ['app/design/frontend/SomeVendor/sometheme/theme.xml'],
                ['vendor/SomeVendor/theme-frontend-sometheme/theme.xml']
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->withConsecutive(
                ['app/design/frontend/SomeVendor/sometheme/registration.php'],
                ['vendor/SomeVendor/theme-frontend-sometheme/registration.php']
            )
            ->willReturn($testRegistration);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->willReturn('Theme SomeVendor/doesntExist does not exist.');

        $this->assertEquals(
            '',
            $this->themeResolver->resolve('SomeVendor/doesntExist')
        );
    }

    public function testGetThemes()
    {
        $testRegistration=$this->getTestRegistration();

        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->withConsecutive(
                [DirectoryList::DIR_DESIGN, true],
                [DirectoryList::DIR_VENDOR, true]
            )
            ->willReturnOnConsecutiveCalls(
                'app/design',
                'vendor'
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('glob')
            ->withConsecutive(
                ['app/design/*/*/*/theme.xml'],
                ['vendor/*/*/theme.xml']
            )
            ->willReturnOnConsecutiveCalls(
                ['app/design/frontend/SomeVendor/sometheme/theme.xml'],
                ['vendor/SomeVendor/theme-frontend-sometheme/theme.xml']
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->withConsecutive(
                ['app/design/frontend/SomeVendor/sometheme/registration.php'],
                ['vendor/SomeVendor/theme-frontend-sometheme/registration.php']
            )
            ->willReturn($testRegistration);

        $this->assertEquals(
            ['SomeVendor/sometheme','SomeVendor/sometheme'],
            $this->themeResolver->getThemes()
        );
    }

    public function testGetThemeName()
    {
        $testRegistration=$this->getTestRegistration();

        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with('app/design/frontend/SomeVendor/sometheme/registration.php')
            ->willReturn($testRegistration);

        $this->assertEquals(
            'SomeVendor/sometheme',
            $this->themeResolver->getThemeName('app/design/frontend/SomeVendor/sometheme/')
        );
    }

    public function testGetThemeNameException()
    {
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException(new FileSystemException);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Unable to find registration.php for theme app/design/frontend/SomeVendor/sometheme/theme.xml');

        $this->themeResolver->getThemeName('app/design/frontend/SomeVendor/sometheme/theme.xml');
    }

    private function getTestRegistration() : string
    {
        return <<<REGISTRATION
<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::THEME,
    'frontend/SomeVendor/sometheme',
    __DIR__
);
REGISTRATION;
    }
}
