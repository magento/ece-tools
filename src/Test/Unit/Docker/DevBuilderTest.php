<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\Docker\BuilderInterface;
use Magento\MagentoCloud\Docker\Config;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\DevBuilder;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DevBuilderTest extends TestCase
{
    /**
     * @var DevBuilder
     */
    private $builder;

    /**
     * @var ServiceFactory|MockObject
     */
    private $serviceFactoryMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->serviceFactoryMock = $this->createTestProxy(ServiceFactory::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->configMock = $this->createMock(Config::class);

        $this->builder = new DevBuilder(
            $this->serviceFactoryMock,
            $this->fileListMock,
            $this->configMock
        );
    }

    public function testGetConfigPath()
    {
        $this->fileListMock->expects($this->once())
            ->method('getMagentoDockerCompose')
            ->willReturn('/ece/docker-compose.yaml');

        $this->assertSame('/ece/docker-compose.yaml', $this->builder->getConfigPath());
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testBuild()
    {
        $config = new Repository([
            BuilderInterface::NGINX_VERSION => 'latest',
            BuilderInterface::PHP_VERSION => '7.0',
            BuilderInterface::DB_VERSION => '10.0'
        ]);

        $this->builder->build($config);
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testBuildFromConfig()
    {
        $config = new Repository();

        $this->configMock->method('getServiceVersion')
            ->willReturnMap([
                [Config::KEY_DB, '10.0'],
            ]);
        $this->configMock->method('getPhpVersion')
            ->willReturn('7.0');

        $build = $this->builder->build($config);

        $this->assertArrayNotHasKey('redis', $build['services']);
        $this->assertArrayNotHasKey('rabbitmq', $build['services']);
        $this->assertArrayNotHasKey('elasticsearch', $build['services']);
        $this->assertArrayHasKey('cli', $build['services']);
        $this->assertArrayHasKey('build', $build['services']);
        $this->assertArrayHasKey('deploy', $build['services']);
        $this->assertArrayHasKey('db', $build['services']);
    }
}
