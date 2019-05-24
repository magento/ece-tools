<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Docker;

use Composer\Console\Application;
use Illuminate\Config\Repository;
use Magento\MagentoCloud\Command\Docker\Build;
use Magento\MagentoCloud\Command\Docker\ConfigConvert;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\Compose\ProductionCompose;
use Magento\MagentoCloud\Docker\ComposeFactory;
use Magento\MagentoCloud\Docker\Config\DistGenerator;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\DevBuilder;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Service\Validator;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $command;

    /**
     * @var ComposeFactory|MockObject
     */
    private $builderFactoryMock;

    /**
     * @var ProductionCompose|MockObject
     */
    private $managerMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var RepositoryFactory|MockObject
     */
    private $repositoryFactoryMock;

    /**
     * @var Repository|MockObject
     */
    private $configMock;

    /**
     * @var DistGenerator|MockObject
     */
    private $distGenerator;

    /**
     * @var Config|MockObject
     */
    private $serviceConfigMock;

    /**
     * @var Validator|MockObject
     */
    private $validatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->builderFactoryMock = $this->createMock(ComposeFactory::class);
        $this->managerMock = $this->createMock(ProductionCompose::class);
        $this->fileMock = $this->createMock(File::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->repositoryFactoryMock = $this->createMock(RepositoryFactory::class);
        $this->configMock = $this->createMock(Repository::class);
        $this->serviceConfigMock = $this->createMock(Config::class);
        $this->validatorMock = $this->createMock(Validator::class);
        $this->distGenerator = $this->createMock(DistGenerator::class);

        $this->serviceConfigMock->expects($this->any())
            ->method('getAllServiceVersions')
            ->willReturn([]);
        $this->validatorMock->expects($this->any())
            ->method('validateVersions')
            ->willReturn([]);

        $this->repositoryFactoryMock->method('create')
            ->willReturn($this->configMock);

        $this->command = new Build(
            $this->builderFactoryMock,
            $this->fileMock,
            $this->environmentMock,
            $this->repositoryFactoryMock,
            $this->serviceConfigMock,
            $this->validatorMock,
            $this->distGenerator
        );
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     */
    public function testExecute()
    {
        /** @var Console\Input\InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(Console\Input\InputInterface::class);
        /** @var Console\Output\OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(Console\Output\OutputInterface::class);

        $inputMock->method('getOption')
            ->willReturnMap([
                [Build::OPTION_PHP, '7.1'],
                [Build::OPTION_DB, '10'],
                [Build::OPTION_NGINX, '1.9'],
                [Build::OPTION_REDIS, '3.2'],
                [Build::OPTION_ES, '2.4'],
                [Build::OPTION_RABBIT_MQ, '3.5'],
                [Build::OPTION_MODE, ComposeFactory::COMPOSE_PRODUCTION]
            ]);

        $this->builderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->managerMock);
        $this->managerMock->expects($this->once())
            ->method('build')
            ->willReturn(['version' => '2']);
        $this->managerMock->expects($this->once())
            ->method('getConfigPath')
            ->willReturn('magento_root/docker-compose.yml');
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('magento_root/docker-compose.yml', "version: '2'\n");
        $this->distGenerator->expects($this->once())
            ->method('generate');

        /** @var Console\Application|MockObject $applicationMock */
        $applicationMock = $this->createMock(Console\Application::class);
        $applicationMock->method('getHelperSet')
            ->willReturn($this->createMock(Console\Helper\HelperSet::class));
        $applicationMock->expects($this->once())
            ->method('find')
            ->willReturnMap([
                [ConfigConvert::NAME, $this->createMock(ConfigConvert::class)],
            ]);

        $this->command->setApplication($applicationMock);
        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     */
    public function testExecuteWithParams()
    {
        /** @var Console\Input\InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(Console\Input\InputInterface::class);
        /** @var Console\Output\OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(Console\Output\OutputInterface::class);

        $this->builderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->managerMock);
        $this->managerMock->expects($this->once())
            ->method('build')
            ->willReturn(['version' => '2']);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('magento_root/docker-compose.yml', "version: '2'\n");
        $inputMock->method('getOption')
            ->willReturnMap([
                [Build::OPTION_PHP, '7.1'],
                [Build::OPTION_DB, '10'],
                [Build::OPTION_NGINX, '1.9'],
                [Build::OPTION_REDIS, '3.2'],
                [Build::OPTION_ES, '2.4'],
                [Build::OPTION_RABBIT_MQ, '3.5'],
                [Build::OPTION_NODE, '6.0'],
                [Build::OPTION_MODE, ComposeFactory::COMPOSE_PRODUCTION],
            ]);
        $this->managerMock->expects($this->once())
            ->method('getConfigPath')
            ->willReturn('magento_root/docker-compose.yml');
        $this->configMock->expects($this->exactly(7))
            ->method('set');
        $this->distGenerator->expects($this->once())
            ->method('generate');

        /** @var Console\Application|MockObject $applicationMock */
        $applicationMock = $this->createMock(Application::class);
        $applicationMock->method('getHelperSet')
            ->willReturn($this->createMock(Console\Helper\HelperSet::class));
        $applicationMock->expects($this->once())
            ->method('find')
            ->willReturnMap([
                [ConfigConvert::NAME, $this->createMock(ConfigConvert::class)],
            ]);

        $this->command->setApplication($applicationMock);
        $this->command->execute($inputMock, $outputMock);
    }

    public function testIsEnabled()
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('isMasterBranch')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertFalse($this->command->isEnabled());
        $this->assertTrue($this->command->isEnabled());
    }
}
