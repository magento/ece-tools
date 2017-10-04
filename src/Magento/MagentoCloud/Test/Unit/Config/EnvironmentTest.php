<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class EnvironmentTest extends TestCase
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var array
     */
    private $environmentData;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentData = $_ENV;
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment = new Environment(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $_ENV = $this->environmentData;
    }

    /**
     * @param array $variables
     */
    private function setVariables(array $variables)
    {
        $_ENV['MAGENTO_CLOUD_VARIABLES'] = base64_encode(json_encode($variables));
    }

    /**
     * @param array $env
     * @param string $key
     * @param mixed $default
     * @param mixed $expected
     * @dataProvider getDataProvider
     */
    public function testGet(array $env, string $key, $default, $expected)
    {
        $_ENV = $env;

        $this->assertSame($expected, $this->environment->get($key, $default));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'string value' => [
                ['some_key' => base64_encode(json_encode('some_value'))],
                'some_key',
                null,
                'some_value',
            ],
            'empty value' => [
                [],
                'some_key',
                null,
                null,
            ],
            'empty value with default' => [
                [],
                'some_key',
                'some_new_value',
                'some_new_value',
            ],
        ];
    }

    public function testGetRestorableDirectories()
    {
        $this->assertSame(
            ['static' => 'pub/static',
            'etc' => 'app/etc',
            'media' => 'pub/media',
            'log' => 'var/log',
            'cloud_flags' => 'var/.cloud_flags'],
            $this->environment->getRestorableDirectories()
        );
    }

    public function testIsDeployStaticContentEnabledByNoFlag()
    {
        $this->setVariables([
            'DO_DEPLOY_STATIC_CONTENT' => 'enabled',
        ]);

        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG)
            ->willReturn(false);

        $this->assertTrue($this->environment->isDeployStaticContent());
    }

    public function testIsDeployStaticContentDisabledByFlag()
    {
        $this->setVariables([
            'DO_DEPLOY_STATIC_CONTENT' => 'enabled',
        ]);

        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG)
            ->willReturn(true);

        $this->assertFalse($this->environment->isDeployStaticContent());
    }

    public function testIsDeployStaticContentDisabledByEnv()
    {
        $this->setVariables([
            'DO_DEPLOY_STATIC_CONTENT' => 'disabled',
        ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content deploy disabled by environment variable');

        $this->assertFalse($this->environment->isDeployStaticContent());
    }

     /**
      * @param string $variableValue
      * @param bool $expected
      * @dataProvider doCleanStaticFilesDataProvider
      */
    public function testDoCleanStaticFiles(string $variableValue, bool $expected)
    {
        $this->setVariables([
            'CLEAN_STATIC_FILES' => $variableValue,
        ]);

        $this->assertSame(
            $expected,
            $this->environment->doCleanStaticFiles()
        );
    }

    public function doCleanStaticFilesDataProvider(): array
    {
        return [
            [Environment::VAL_DISABLED, false],
            [Environment::VAL_ENABLED, true],
            ['', true],
        ];
    }

    /**
     * @param string $variableValue
     * @param bool $expected
     * @dataProvider isStaticContentSymlinkOnDataProvider
     */
    public function testIsStaticContentSymlinkOn(string $variableValue, bool $expected)
    {
        $this->setVariables([
            'STATIC_CONTENT_SYMLINK' => $variableValue,
        ]);


        $this->assertSame(
            $expected,
            $this->environment->isStaticContentSymlinkOn()
        );
    }

    public function isStaticContentSymlinkOnDataProvider()
    {
        return [
            [Environment::VAL_DISABLED, false],
            [Environment::VAL_ENABLED, true],
            ['', true],
        ];
    }

    public function testGetDefaultCurrency()
    {
        $this->assertSame(
            'USD',
            $this->environment->getDefaultCurrency()
        );
    }

    public function flagDataProvider()
    {
        return [
            ['path' => '.some_flag', 'flagState' => true],
            ['path' => 'what/the/what/.some_flag', 'flagState' => false]
        ];
    }

    /**
     * @dataProvider flagDataProvider
     */
    public function testHasFlag($path, $flagState)
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with("magento_root/$path")
            ->willReturn($flagState);

        $this->assertSame($flagState, $this->environment->hasFlag($path));
    }

    public function isVariableDisabledProvider()
    {
        return [
            ['name' => 'SOME_ENV_VAR', 'value' => 'disabled', 'result' => true],
            ['name' => 'SOME_ENV_VAR', 'value' => 'enabled', 'result' => false],
            ['name' => 'SOME_ENV_VAR', 'value' => '', 'result' => false],
        ];
    }

    /**
     * @dataProvider isVariableDisabledProvider
     */
    public function testIsVariableDisabled($name, $value, $result)
    {
        $this->setVariables([
            $name => $value,
        ]);

        $this->assertSame($result, $this->environment->isVariableDisabled($name));
    }

    /**
     * @dataProvider flagDataProvider
     */
    public function testSetFlag($path, $flagState)
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with("magento_root/$path")
            ->willReturn($flagState);
        if ($flagState) {
            $this->loggerMock->expects($this->once())
                ->method('info')
                ->with("Set flag: magento_root/$path");
        }

        $this->assertSame($flagState, $this->environment->setFlag($path));
    }

    public function clearFlagDataProvider()
    {
        return [
            [
                'root' => 'magento_root',
                'path' => '.some_flag',
                'flag' => 'magento_root/.some_flag',
                'flagState' => true,
                'deleteResult' => true,
                'logs' => ["Deleted flag: magento_root/.some_flag"],
                'result' => true
            ],
            [
                'root' => 'magento_root',
                'path' => '.some_flag',
                'flag' => 'magento_root/.some_flag',
                'flagState' => false,
                'deleteResult' => false,
                'logs' => ["magento_root/.some_flag already removed"],
                'result' => true
            ],
            [
                'root' => 'magento_root',
                'path' => '.some_flag',
                'flag' => 'magento_root/.some_flag',
                'flagState' => true,
                'deleteResult' => false,
                'logs' => [],
                'result' => false
            ],
        ];
    }

    /**
     * @dataProvider clearFlagDataProvider
     */
    public function testClearFlag($root, $path, $flag, $flagState, $deleteResult, $logs, $result)
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($root);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($flag)
            ->willReturn($flagState);
        if ($flagState) {
            $this->fileMock->expects($this->once())
                ->method('deleteFile')
                ->with("$flag")
                ->willReturn($deleteResult);
        }
        $this->loggerMock->expects($this->exactly(count($logs)))
            ->method('info')
            ->withConsecutive($logs);

        $this->assertSame($result, $this->environment->clearFlag($path));
    }

    public function symlinkContentsProvider()
    {
        return [
            [
                'targetDir' => 'some/dir',
                'contents' => ['/some/dir/thing1', '/some/dir/thing2'],
                'linkDir' => 'another/path',
                'symlinkResult' => true
            ],
            [
                'targetDir' => 'some/dir',
                'contents' => ['/some/dir/thing1', '/some/dir/thing2'],
                'linkDir' => 'another/path',
                'symlinkResult' => false
            ],
            [
                'targetDir' => 'some/dir',
                'contents' => [],
                'linkDir' => 'another/path',
                'symlinkResult' => false
            ],
        ];
    }

    /**
     * @dataProvider symlinkContentsProvider
     */
    public function testSymlinkDirectoryContents($targetDir, $contents, $linkDir, $symlinkResult)
    {
        $this->fileMock->expects($this->once())
            ->method('readDirectory')
            ->with($targetDir)
            ->willReturn($contents);
        $this->fileMock->expects($this->exactly(count($contents)))
            ->method('symlink')
            ->willReturn($symlinkResult);
        if ($symlinkResult) {
            $this->loggerMock->expects($this->exactly(count($contents)))
                ->method('info')
                ->withConsecutive(...(array_map(function ($thing) use ($targetDir, $linkDir) {
                        return ["Symlinked $linkDir/" . basename($thing) . " to $thing"];
                }, $contents)));
        }
        $this->environment->symlinkDirectoryContents($targetDir, $linkDir);
    }
}
