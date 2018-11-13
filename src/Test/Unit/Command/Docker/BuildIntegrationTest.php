<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Docker;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Command\Docker\BuildIntegration;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\BuilderFactory;
use Magento\MagentoCloud\Docker\BuilderInterface;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class BuildIntegrationTest extends TestCase
{
    /**
     * @var BuildIntegration
     */
    private $command;

    /**
     * @var BuilderFactory|MockObject
     */
    private $builderFactoryMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var RepositoryFactory|MockObject
     */
    private $configFactoryMock;

    /**
     * @var BuilderInterface|MockObject
     */
    private $builderMock;

    /**
     * @var Repository|MockObject
     */
    private $configMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->builderFactoryMock = $this->createMock(BuilderFactory::class);
        $this->fileMock = $this->createMock(File::class);
        $this->configFactoryMock = $this->createMock(RepositoryFactory::class);
        $this->builderMock = $this->getMockForAbstractClass(BuilderInterface::class);
        $this->configMock = $this->getMockForAbstractClass(Repository::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->configFactoryMock->method('create')
            ->willReturn($this->configMock);

        $this->command = new BuildIntegration(
            $this->builderFactoryMock,
            $this->fileMock,
            $this->configFactoryMock,
            $this->environmentMock
        );
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     */
    public function testExecute()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->builderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->builderMock);
        $this->builderMock->expects($this->once())
            ->method('build')
            ->willReturn(['version' => '2']);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('magento_root/docker-compose.yml', "version: '2'\n");
        $inputMock->method('getArgument')
            ->willReturnMap([
                [BuildIntegration::ARGUMENT_PHP, '7.1'],
                [BuildIntegration::ARGUMENT_DB, '10.0'],
                [BuildIntegration::ARGUMENT_NGINX, '1.9'],
            ]);
        $this->builderMock->expects($this->once())
            ->method('getConfigPath')
            ->willReturn('magento_root/docker-compose.yml');
        $this->configMock->expects($this->exactly(3))
            ->method('set')
            ->withConsecutive(
                [BuilderInterface::PHP_VERSION, '7.1'],
                [BuilderInterface::DB_VERSION, '10.0'],
                [BuilderInterface::NGINX_VERSION, '1.9']
            );

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
