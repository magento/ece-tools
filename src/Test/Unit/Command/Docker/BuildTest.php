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
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\Compose\DeveloperCompose;
use Magento\MagentoCloud\Docker\Compose\ProductionCompose;
use Magento\MagentoCloud\Docker\ComposeFactory;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ServiceMismatchException;
use Magento\MagentoCloud\Service\Validator;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console;
use Symfony\Component\Console\Tester\CommandTester;
use Magento\MagentoCloud\Docker\Config\Dist\Generator;

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
     * @var Config|MockObject
     */
    private $serviceConfigMock;

    /**
     * @var Validator|MockObject
     */
    private $validatorMock;

    /**
     * @var Build\Writer|MockObject
     */
    private $writerMock;

    /**
     * @var Generator|MockObject
     */
    private $distGeneratorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->builderFactoryMock = $this->createMock(ComposeFactory::class);
        $this->managerMock = $this->createMock(ProductionCompose::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->repositoryFactoryMock = $this->createMock(RepositoryFactory::class);
        $this->configMock = $this->createMock(Repository::class);
        $this->serviceConfigMock = $this->createMock(Config::class);
        $this->validatorMock = $this->createMock(Validator::class);
        $this->writerMock = $this->createMock(Build\Writer::class);
        $this->distGeneratorMock = $this->createMock(Generator::class);

        $this->serviceConfigMock->method('getAllServiceVersions')
            ->willReturn([]);
        $this->validatorMock->method('validateVersions')
            ->willReturn([]);
        $this->repositoryFactoryMock->method('create')
            ->willReturn($this->configMock);

        $this->command = new Build(
            $this->builderFactoryMock,
            $this->environmentMock,
            $this->repositoryFactoryMock,
            $this->serviceConfigMock,
            $this->validatorMock,
            $this->writerMock,
            $this->distGeneratorMock
        );
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     * @throws UndefinedPackageException
     * @throws ServiceMismatchException
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
                [Build::OPTION_MODE, ComposeFactory::COMPOSE_PRODUCTION],
                [Build::OPTION_SYNC_ENGINE, DeveloperCompose::SYNC_ENGINE_DOCKER_SYNC],
            ]);

        $this->builderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->managerMock);
        $this->writerMock->expects($this->once())
            ->method('write')
            ->with($this->managerMock, $this->configMock);
        $this->distGeneratorMock->expects($this->once())
            ->method('generate');

        /** @var Console\Application|MockObject $applicationMock */
        $applicationMock = $this->createMock(Console\Application::class);
        $applicationMock->method('getHelperSet')
            ->willReturn($this->createMock(Console\Helper\HelperSet::class));

        $this->command->setApplication($applicationMock);
        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     * @throws UndefinedPackageException
     * @throws ServiceMismatchException
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
                [Build::OPTION_SYNC_ENGINE, DeveloperCompose::SYNC_ENGINE_DOCKER_SYNC],
            ]);
        $this->configMock->expects($this->exactly(8))
            ->method('set');
        $this->writerMock->expects($this->once())
            ->method('write')
            ->with($this->managerMock, $this->configMock);
        $this->distGeneratorMock->expects($this->once())
            ->method('generate');

        /** @var Console\Application|MockObject $applicationMock */
        $applicationMock = $this->createMock(Application::class);
        $applicationMock->method('getHelperSet')
            ->willReturn($this->createMock(Console\Helper\HelperSet::class));

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

    /**
     * @param string $optionName
     *
     * @expectedException \Symfony\Component\Console\Exception\InvalidOptionException
     *
     * @dataProvider executeWithEmptyOptionDataProvider
     */
    public function testExecuteWithEmptyOption(string $optionName)
    {
        $this->expectExceptionMessage(sprintf('The "--%s" option requires a value', $optionName));

        $tester = new CommandTester($this->command);

        $tester->execute(['--' . $optionName => null]);
    }

    /**
     * @return array
     */
    public function executeWithEmptyOptionDataProvider(): array
    {
        return [
            [Build::OPTION_PHP],
            [Build::OPTION_DB],
            [Build::OPTION_NGINX],
            [Build::OPTION_REDIS],
            [Build::OPTION_ES],
            [Build::OPTION_RABBIT_MQ],
            [Build::OPTION_NODE],
            [Build::OPTION_MODE],
        ];
    }
}
