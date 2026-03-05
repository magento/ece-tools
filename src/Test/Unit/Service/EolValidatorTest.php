<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Carbon\Carbon;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Service\Detector\DatabaseType;
use Magento\MagentoCloud\Service\EolValidator;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Util\YamlNormalizer;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
#[AllowMockObjectsWithoutExpectations]
class EolValidatorTest extends TestCase
{
    use PHPMock;

    /**
     * @var EolValidator
     */
    private $validator;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var ServiceFactory|MockObject
     */
    private $serviceFactoryMock;

    /**
     * @var DatabaseType|MockObject
     */
    private $databaseTypeMock;

    /**
     * @var YamlNormalizer|MockObject
     */
    private YamlNormalizer $yamlNormalizerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_get_contents');
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_exists');

        Carbon::setTestNow(Carbon::create(2019, 12, 2));

        $this->fileListMock       = $this->createMock(FileList::class);
        $this->fileMock           = $this->createPartialMock(File::class, ['isExists', 'fileGetContents']);
        $this->serviceFactoryMock = $this->createMock(ServiceFactory::class);
        $this->databaseTypeMock   = $this->createMock(DatabaseType::class);
        $this->yamlNormalizerMock = $this->createMock(YamlNormalizer::class);

        $this->validator = new EolValidator(
            $this->fileListMock,
            $this->fileMock,
            $this->serviceFactoryMock,
            $this->databaseTypeMock,
            $this->yamlNormalizerMock
        );
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    /**
     * Test compatible version.
     *
     * @return void
     */
    public function testCompatibleVersion(): void
    {
        $configsPath = __DIR__ . '/_file/eol_2.yaml';

        $yamlContent = <<<YAML
php:
  - version: '7.0'
    eol: 2018-12-01
  - version: '7.1'
    eol: 2019-12-01
elasticsearch:
    - version: '6.5'
      eol: 2020-05-14
YAML;

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($configsPath)
            ->willReturn($yamlContent);

        $this->yamlNormalizerMock->expects($this->once())
            ->method('normalize')
            ->willReturnArgument(0);

        $serviceName = ServiceInterface::NAME_ELASTICSEARCH;
        $serviceVersion = '8.11';

        $this->assertEquals([], $this->validator->validateService($serviceName, $serviceVersion));
    }

    /**
     * Test validation with no configurations provided.
     *
     * @return void
     */
    public function testValidateServiceWithoutConfigs(): void
    {
        $configsPath = __DIR__ . '/_file/eol_2.yaml';

        $yamlContent = <<<YAML
php:
  - version: '7.0'
    eol: 2018-12-01
  - version: '7.1'
    eol: 2019-12-01
elasticsearch:
  - version: '6.5'
    eol: 2020-05-14
YAML;

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($configsPath)
            ->willReturn($yamlContent);

        $this->yamlNormalizerMock->expects($this->once())
            ->method('normalize')
            ->willReturnArgument(0);

        $serviceName = ServiceInterface::NAME_RABBITMQ;
        $serviceVersion = '3.5';

        $this->assertEquals(
            [],
            $this->validator->validateService($serviceName, $serviceVersion)
        );
    }

    /**
     * Test service validation.
     *
     * @return void
     */
    public function testValidateServiceEol(): void
    {
        $this->setupEolConfigMocks();
        $this->setupServiceMocks();

        $this->assertEquals([], $this->validator->validateServiceEol());
    }

    /**
     * Set up EOL configuration file mocks
     *
     * @return void
     */
    private function setupEolConfigMocks(): void
    {
        $configsPath = __DIR__ . '/_file/eol.yaml';
        $yamlContent = $this->getEmptyEolYamlContent();

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($configsPath)
            ->willReturn($yamlContent);

        $this->yamlNormalizerMock->expects($this->once())
            ->method('normalize')
            ->willReturnArgument(0);
    }

    /**
     * Set up service factory and database type mocks
     *
     * @return void
     */
    private function setupServiceMocks(): void
    {
        $this->databaseTypeMock->expects($this->once())
            ->method('getServiceName')
            ->willReturn(ServiceInterface::NAME_DB_MARIA);

        $serviceVersions = [
            'php' => '7.1',
            'elasticsearch' => '5.2',
            'rabbitmq' => '3.5',
            'redis' => '3.2',
            'redis-session' => '3.2',
            'mariadb' => '10.2',
            'valkey' => '8.0',
            'valkey-session' => '8.0',
            'opensearch' => '2',
            'activemq-artemis' => '2.42',
        ];

        $serviceMocks = [];
        foreach ($serviceVersions as $serviceName => $version) {
            $mock = $this->createMock(ServiceInterface::class);
            $mock->expects($this->once())
                ->method('getVersion')
                ->willReturn($version);
            $serviceMocks[$serviceName] = $mock;
        }

        $this->serviceFactoryMock->expects($this->exactly(10))
            ->method('create')
            ->willReturnCallback(fn($param) => $serviceMocks[$param]);
    }

    /**
     * Get empty EOL YAML content for testing
     *
     * @return string
     */
    private function getEmptyEolYamlContent(): string
    {
        return <<<YAML
# Service EOLs (YYYY-MM-DD).
php:
  - version: '7.0'
    eol: null
  - version: '7.1'
    eol: null
  - version: '7.2'
    eol: null
mariadb:
  - version: '10.0'
    eol: null
  - version: '10.1'
    eol: null
  - version: '10.2'
    eol: null
elasticsearch:
  - version: '1.7'
    eol: null
  - version: '2.4'
    eol: null
  - version: '5.2'
    eol: null
  - version: '6.5'
    eol: null
rabbitmq:
  - version: '3.5'
    eol: null
  - version: '3.7'
    eol: null
redis:
  - version: '3.2'
    eol: null
  - version: '4.0'
    eol: null
  - version: '5.0'
    eol: null
  - version: '5.2'
    eol: null
YAML;
    }

    /**
     * Test service approaching EOL.
     *
     * @return void
     */
    public function testValidateNoticeMessage(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 11, 1));

        $configsPath = __DIR__ . '/_file/eol_2.yaml';

        $yamlContent = <<<YAML
php:
  - version: '7.0'
    eol: 2018-12-01
  - version: '7.1'
    eol: 2019-12-01
elasticsearch:
  - version: '6.5'
    eol: 2020-05-14
YAML;

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($configsPath)
            ->willReturn($yamlContent);

        $this->yamlNormalizerMock->expects($this->once())
            ->method('normalize')
            ->willReturnArgument(0);

        $serviceName = ServiceInterface::NAME_PHP;
        $serviceVersion = '7.1.30';
        $eolDate = Carbon::create(2019, 12, 1);
        $message = sprintf(
            '%s %s is approaching EOL (%s).',
            $serviceName,
            $serviceVersion,
            date_format($eolDate, 'Y-m-d')
        );

        $this->assertEquals(
            [ValidatorInterface::LEVEL_NOTICE => $message],
            $this->validator->validateService($serviceName, $serviceVersion)
        );
    }

    /**
     * Test service passed EOL.
     *
     * @return void
     */
    public function testValidateWarningMessage(): void
    {
        $configsPath = __DIR__ . '/_file/eol_2.yaml';

        $yamlContent = <<<YAML
php:
  - version: '7.0'
    eol: 2018-12-01
  - version: '7.1'
    eol: 2019-12-01
elasticsearch:
  - version: '6.5'
    eol: 2020-05-14
YAML;

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($configsPath)
            ->willReturn($yamlContent);

        $this->yamlNormalizerMock->expects($this->once())
            ->method('normalize')
            ->willReturnArgument(0);

        $serviceName = ServiceInterface::NAME_PHP;
        $serviceVersion = '7.0';
        $eolDate = Carbon::create(2018, 12, 1);
        $message = sprintf(
            '%s %s has passed EOL (%s).',
            $serviceName,
            $serviceVersion,
            date_format($eolDate, 'Y-m-d')
        );

        $this->assertEquals(
            [ValidatorInterface::LEVEL_WARNING => $message],
            $this->validator->validateService($serviceName, $serviceVersion)
        );
    }

    /**
     * Test for getServiceConfigs method
     *
     * @return void
     */
    public function testGetServiceConfigsReturnsExpectedServiceConfig(): void
    {
        $serviceName = 'php';
        $configPath = '/tmp/eol.yaml';

        $yamlContent = <<<YAML
php:
  version: "8.2"
  eol: "2025-12-31"
nginx:
  version: "1.23"
YAML;

        $parsedArray = [
            'php' => [
                'version' => '8.2',
                'eol' => '2025-12-31'
            ],
            'nginx' => [
                'version' => '1.23'
            ]
        ];

        // Mock: return file path
        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configPath);

        // Mock: file exists
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configPath)
            ->willReturn(true);

        // Mock: read file contents
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($configPath)
            ->willReturn($yamlContent);

        // Mock: YAML normalizer result
        $this->yamlNormalizerMock->expects($this->once())
            ->method('normalize')
            ->willReturn($parsedArray);

        // Access private method using reflection
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('getServiceConfigs');

        // Execute
        $result = $method->invokeArgs($this->validator, [$serviceName]);

        // Assert
        $this->assertEquals(
            [
                'version' => '8.2',
                'eol' => '2025-12-31'
            ],
            $result
        );
    }
}
