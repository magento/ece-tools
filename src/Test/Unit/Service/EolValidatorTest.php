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
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
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
     * @inheritDoc
     */
    protected function setUp(): void
    {
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_get_contents');
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_exists');

        Carbon::setTestNow(Carbon::create(2019, 12, 2));

        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createPartialMock(File::class, ['isExists']);
        $this->serviceFactoryMock = $this->createMock(ServiceFactory::class);
        $this->databaseTypeMock = $this->createMock(DatabaseType::class);

        $this->validator = new EolValidator(
            $this->fileListMock,
            $this->fileMock,
            $this->serviceFactoryMock,
            $this->databaseTypeMock
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
     */
    public function testCompatibleVersion()
    {
        $configsPath = __DIR__ . '/_file/eol_2.yaml';

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

        $serviceName = ServiceInterface::NAME_ELASTICSEARCH;
        $serviceVersion = '6.5';

        $this->assertEquals([], $this->validator->validateService($serviceName, $serviceVersion));
    }

    /**
     * Test validation with no configurations provided.
     */
    public function testValidateServiceWithoutConfigs()
    {
        $configsPath = __DIR__ . '/_file/eol_2.yaml';

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

        $serviceName = ServiceInterface::NAME_RABBITMQ;
        $serviceVersion = '3.5';

        $this->assertEquals(
            [],
            $this->validator->validateService($serviceName, $serviceVersion)
        );
    }

    /**
     * Test service validation.
     */
    public function testValidateServiceEol()
    {
        $configsPath = __DIR__ . '/_file/eol.yaml';

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

        $this->databaseTypeMock->expects($this->once())
            ->method('getServiceName')
            ->willReturn(ServiceInterface::NAME_DB_MARIA);
        $service1 = $this->createMock(ServiceInterface::class);
        $service1->expects($this->once())
            ->method('getVersion')
            ->willReturn('7.1');
        $service2 = $this->createMock(ServiceInterface::class);
        $service2->expects($this->once())
            ->method('getVersion')
            ->willReturn('5.2');
        $service3 = $this->createMock(ServiceInterface::class);
        $service3->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.5');
        $service4 = $this->createMock(ServiceInterface::class);
        $service4->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.2');
        $service5 = $this->createMock(ServiceInterface::class);
        $service5->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.2');
        $service6 = $this->createMock(ServiceInterface::class);
        $service6->expects($this->once())
            ->method('getVersion')
            ->willReturn('10.2');

        $this->serviceFactoryMock->expects($this->exactly(6))
            ->method('create')
            ->withConsecutive(
                ['php'],
                ['elasticsearch'],
                ['rabbitmq'],
                ['redis'],
                ['redis-session'],
                ['mariadb']
            )
            ->willReturnOnConsecutiveCalls(
                $service1,
                $service2,
                $service3,
                $service4,
                $service5,
                $service6
            );

        $this->assertEquals(
            [],
            $this->validator->validateServiceEol()
        );
    }

    /**
     * Test service approaching EOL.
     */
    public function testValidateNoticeMessage()
    {
        Carbon::setTestNow(Carbon::create(2019, 11, 1));

        $configsPath = __DIR__ . '/_file/eol_2.yaml';

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

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
     */
    public function testValidateWarningMessage()
    {
        $configsPath = __DIR__ . '/_file/eol_2.yaml';

        $this->fileListMock->expects($this->once())
            ->method('getServiceEolsConfig')
            ->willReturn($configsPath);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configsPath)
            ->willReturn(true);

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
}
