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

    public function testSetFlagStaticDeployInBuild()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Setting flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG);

        $this->environment->setFlagStaticDeployInBuild();
    }

    public function testRemoveFlagStaticContentInBuild()
    {
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Removing flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        $this->fileMock->expects($this->once())
            ->method('deleteFile')
            ->with('magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG);

        $this->environment->removeFlagStaticContentInBuild();
    }

    public function testRemoveFlagStaticContentInBuildDisabled()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG)
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->fileMock->expects($this->never())
            ->method('deleteFile');

        $this->environment->removeFlagStaticContentInBuild();
    }

    public function testIsStaticDeployInBuild()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG);

        $this->environment->isStaticDeployInBuild();
    }

    public function testGetWritableDirectories()
    {
        $this->assertSame(
            ['var', 'app/etc', 'pub/media'],
            $this->environment->getWritableDirectories()
        );
    }

    public function testGetRecoverableDirectories()
    {
        $this->assertSame(
            ['app/etc', 'pub/media'],
            $this->environment->getRecoverableDirectories()
        );
    }

    public function testIsDeployStaticContent()
    {
        $this->setVariables([
            'DO_DEPLOY_STATIC_CONTENT' => 'disabled',
        ]);

        $this->assertSame(
            false,
            $this->environment->isDeployStaticContent()
        );
    }

    /**
     * @param bool $isExists
     * @dataProvider isDeployStaticContentToBeEnabledDataProvider
     */
    public function testIsDeployStaticContentToBeEnabled(bool $isExists)
    {
        $this->setVariables([
            'DO_DEPLOY_STATIC_CONTENT' => 'enabled',
        ]);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn($isExists);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Flag DO_DEPLOY_STATIC_CONTENT is set to ' . (!$isExists ? 'enabled' : 'disabled'));

        $this->assertSame(
            !$isExists,
            $this->environment->isDeployStaticContent()
        );
    }

    /**
     * @return array
     */
    public function isDeployStaticContentToBeEnabledDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
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

    public function testGetEnvironmentIdIfEnvVarExists()
    {
        $testString = 'some_id';
        $_ENV['MAGENTO_CLOUD_ENVIRONMENT'] = $testString;
        $this->assertSame($testString, $this->environment->getEnvironmentId());
    }

    public function testGetEnvironmentIdIfEnvVarNotExists()
    {
        unset($_ENV['MAGENTO_CLOUD_ENVIRONMENT']);
        $this->assertNull($this->environment->getEnvironmentId());
    }

    /**
     * @param string $mageCloudEnv
     * @param string $pathToEnvIdFile
     * @param bool $expectedResult
     * @dataProvider hasEnvironmentChangedDataProvider
     */
    public function testHasEnvironmentChanged(string $mageCloudEnv, string $pathToEnvIdFile, bool $expectedResult)
    {
        $_ENV['MAGENTO_CLOUD_ENVIRONMENT'] = $mageCloudEnv;
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($pathToEnvIdFile);
        $this->assertSame($this->environment->hasEnvironmentChanged(), $expectedResult, $pathToEnvIdFile);
    }

    public function hasEnvironmentChangedDataProvider()
    {
        $root = __DIR__ . '/_file/Environment';
        return [
            /**
             * The file not exists
             */
            [
                'mageCloudEnv' => 'some_id',
                'pathToEnvIdFile' => '/wrong/path/to/.env_id',
                'expectedResult' => false,
            ],
            /**
             * The environment variable identifier in the file does not equal
             * the environment variable MAGENTO_CLOUD_ENVIRONMENT
             */
            [
                'mageCloudEnv' => 'other_some_id',
                'pathToEnvIdFile' => $root,
                'expectedResult' => true,
            ],
            /**
             * The environment variable identifier in the file equal
             * the environment variable MAGENTO_CLOUD_ENVIRONMENT
             */
            [
                'mageCloudEnv' => 'some_id',
                'pathToEnvIdFile' => $root,
                'expectedResult' => false,
            ],
        ];
    }

    public function testSyncEnvironmentId()
    {
        $_ENV['MAGENTO_CLOUD_ENVIRONMENT'] = 'some_id';
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with('magento_root/var');
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('magento_root/var/.env_id', 'some_id');
        $this->environment->syncEnvironmentId();
    }
}
