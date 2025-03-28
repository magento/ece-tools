<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Environment\ReaderInterface;
use Magento\MagentoCloud\Config\EnvironmentData;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\System\Variables;
use Magento\MagentoCloud\Config\SystemConfigInterface;
use Magento\MagentoCloud\PlatformVariable\DecoderInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class EnvironmentDataTest extends TestCase
{
    use PHPMock;

    /**
     * @var Variables
     */
    private $variable;

    /**
     * @var EnvironmentData
     */
    private $environmentData;

    /**
     * @var DecoderInterface|MockObject
     */
    private $decoderMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        /** @var MockObject|ReaderInterface $environmentReaderMock */
        $environmentReaderMock = $this->getMockForAbstractClass(ReaderInterface::class);
        /** @var MockObject|Schema $schemaMock */
        $schemaMock = $this->createMock(Schema::class);

        $schemaMock->method('getDefaults')
            ->with(SystemConfigInterface::SYSTEM_VARIABLES)
            ->willReturn([
                SystemConfigInterface::VAR_ENV_RELATIONSHIPS => 'MAGENTO_CLOUD_RELATIONSHIPS',
                SystemConfigInterface::VAR_ENV_ROUTES => 'MAGENTO_CLOUD_ROUTES',
                SystemConfigInterface::VAR_ENV_VARIABLES => 'MAGENTO_CLOUD_VARIABLES',
                SystemConfigInterface::VAR_ENV_APPLICATION => 'MAGENTO_CLOUD_APPLICATION',
                SystemConfigInterface::VAR_ENV_ENVIRONMENT => 'MAGENTO_CLOUD_ENVIRONMENT',
            ]);

        $this->variable = new Variables(
            $environmentReaderMock,
            $schemaMock
        );

        $this->decoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->environmentData = new EnvironmentData(
            $this->variable,
            $this->decoderMock,
            $this->fileListMock,
            $this->fileMock
        );
    }

    public function testGetEnv(): void
    {
        $_ENV = ['some_key' => 'some_value'];

        $this->assertEquals('some_value', $this->environmentData->getEnv('some_key'));
    }

    public function testGetEnvFromFunction(): void
    {
        $_ENV = [];
        $getEnvMock = $this->getFunctionMock('Magento\MagentoCloud\Config', 'getenv');
        $getEnvMock->expects($this->any())
            ->with('some_key')
            ->willReturn('some_value');

        $this->assertEquals('some_value', $this->environmentData->getEnv('some_key'));
    }

    /**
     * @param string $envVariableName
     * @param string $methodName
     *
     * @dataProvider getVariablesDataProvider
     */
    public function testGetVariables(string $envVariableName, string $methodName): void
    {
        $decodedValue = base64_encode(json_encode(['some_value']));
        $_ENV = [$envVariableName => $decodedValue];

        $this->decoderMock->expects($this->once())
            ->method('decode')
            ->with($decodedValue)
            ->willReturn(['some_value']);

        $this->assertEquals(['some_value'], call_user_func([$this->environmentData, $methodName]));
        /** Lazy loading */
        $this->assertEquals(['some_value'], call_user_func([$this->environmentData, $methodName]));
    }

    /**
     * @return array
     */
    public function getVariablesDataProvider(): array
    {
        return [
            ['MAGENTO_CLOUD_ROUTES', 'getRoutes'],
            ['MAGENTO_CLOUD_VARIABLES', 'getVariables'],
            ['MAGENTO_CLOUD_RELATIONSHIPS', 'getRelationships'],
            ['MAGENTO_CLOUD_APPLICATION', 'getApplication'],
        ];
    }

    public function testGetBranchName(): void
    {
        $_ENV['MAGENTO_CLOUD_ENVIRONMENT'] = 'production';

        $this->assertEquals('production', $this->environmentData->getBranchName());
    }

    // Following tests to see if .magento.app.yaml can be read (includes file missing)
    public function testGetApplicationWithNoSystemVariablesFileExists(): void
    {
        $_ENV = null;

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('[]');

        $this->assertEquals([], $this->environmentData->getApplication());
    }

    public function testGetApplicationWithNoSystemVariablesFileNotExists(): void
    {
        $_ENV = null;
        $exception = new FilesystemException('.magento.app.yaml not exist');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException($exception);

        $this->assertEquals([], $this->environmentData->getApplication());
    }

    public function testGetMageMode(): void
    {
        $this->assertNull($this->environmentData->getMageMode());

        $mode = 'some_mode';
        $_ENV['MAGE_MODE'] = $mode;
        $this->assertEquals($mode, $this->environmentData->getMageMode());

        //check that value was taken from cache
        $_ENV['MAGE_MODE'] = 'new value';
        $this->assertEquals($mode, $this->environmentData->getMageMode());
    }
}
