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
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\PlatformVariable\DecoderInterface;
use Magento\MagentoCloud\Util\YamlNormalizer;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
#[AllowMockObjectsWithoutExpectations]
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
     * @var YamlNormalizer|MockObject
     */
    private YamlNormalizer $yamlNormalizerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        /** @var MockObject|ReaderInterface $environmentReaderMock */
        $environmentReaderMock = $this->createStub(ReaderInterface::class);

        /** @var MockObject|Schema $schemaMock */
        $schemaMock = $this->createStub(Schema::class);

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

        $this->decoderMock        = $this->createMock(DecoderInterface::class);
        $this->fileListMock       = $this->createMock(FileList::class);
        $this->fileMock           = $this->createMock(File::class);
        $this->yamlNormalizerMock = $this->createMock(YamlNormalizer::class);

        $this->environmentData = new EnvironmentData(
            $this->variable,
            $this->decoderMock,
            $this->fileListMock,
            $this->fileMock,
            $this->yamlNormalizerMock
        );
    }

    /**
     * Test for getEnv method.
     *
     * @return void
     */
    public function testGetEnv(): void
    {
        $_ENV = ['some_key' => 'some_value'];

        $this->assertEquals('some_value', $this->environmentData->getEnv('some_key'));
    }

    /**
     * Test for getEnv method when value is got from getenv function
     *
     * @return void
     */
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
     * Test for getVariables method.
     *
     * @param string $envVariableName
     * @param string $methodName
     * @return void
     * @dataProvider getVariablesDataProvider
     */
    #[DataProvider('getVariablesDataProvider')]
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
     * Data provider for testGetVariables.
     *
     * @return array
     */
    public static function getVariablesDataProvider(): array
    {
        return [
            ['MAGENTO_CLOUD_ROUTES', 'getRoutes'],
            ['MAGENTO_CLOUD_VARIABLES', 'getVariables'],
            ['MAGENTO_CLOUD_RELATIONSHIPS', 'getRelationships'],
            ['MAGENTO_CLOUD_APPLICATION', 'getApplication'],
        ];
    }

    /**
     * Test for getBranchName method.
     *
     * @return void
     */
    public function testGetBranchName(): void
    {
        $_ENV['MAGENTO_CLOUD_ENVIRONMENT'] = 'production';

        $this->assertEquals('production', $this->environmentData->getBranchName());
    }

    /**
     * Test for getApplication method when .magento.app.yaml file exists.
     * Following tests to see if .magento.app.yaml can be read (includes file missing)
     * and parsed correctly
     *
     * @return void
     */
    public function testGetApplicationWithNoSystemVariablesFileExists(): void
    {
        $_ENV = null;

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('[]');
        $this->yamlNormalizerMock
            ->method('normalize')
            ->willReturnCallback(fn($data) => $data);

        $this->assertEquals([], $this->environmentData->getApplication());
    }

    /**
     * Test for getApplication method when .magento.app.yaml file does not exist.
     *
     * @return void
     */
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
        $this->yamlNormalizerMock
            ->expects($this->never())
            ->method('normalize');

        $this->assertEquals([], $this->environmentData->getApplication());
    }

    /**
     * Test for getMageMode method.
     *
     * @return void
     */
    public function testGetMageMode(): void
    {
        $this->assertNull($this->environmentData->getMageMode());

        $mode = 'some_mode';
        $_ENV['MAGE_MODE'] = $mode;
        $this->assertEquals($mode, $this->environmentData->getMageMode());

        // Check that value was taken from cache
        $_ENV['MAGE_MODE'] = 'new value';
        $this->assertEquals($mode, $this->environmentData->getMageMode());
    }
}
