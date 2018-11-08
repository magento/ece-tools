<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\BuilderInterface;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\ServiceInterface;
use Magento\MagentoCloud\Docker\IntegrationBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class IntegrationBuilderTest extends TestCase
{
    /**
     * @var IntegrationBuilder
     */
    private $builder;

    /**
     * @var RepositoryFactory|MockObject
     */
    private $repositoryFactoryMock;

    /**
     * @var Repository|MockObject
     */
    private $configMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->repositoryFactoryMock = $this->createMock(RepositoryFactory::class);
        $this->configMock = $this->getMockForAbstractClass(Repository::class);

        $this->repositoryFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->configMock);

        $this->builder = new IntegrationBuilder(
            $this->repositoryFactoryMock
        );
    }

    /**
     * @param string $version
     * @dataProvider setNginxVersionDataProvider
     * @throws \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     */
    public function testSetNginxVersion(string $version)
    {
        $this->configMock->expects($this->once())
            ->method('set')
            ->with('nginx.version', $version);

        $this->builder->setNginxVersion($version);
    }

    /**
     * @return array
     */
    public function setNginxVersionDataProvider(): array
    {
        return [
            ['1.9'],
            [IntegrationBuilder::DEFAULT_NGINX_VERSION,],
        ];
    }

    /**
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Service nginx:2 is not supported
     */
    public function testSetNginxWithError()
    {
        $this->configMock->expects($this->never())
            ->method('set');

        $this->builder->setNginxVersion('2');
    }

    /**
     * @param string $version
     * @dataProvider setPhpVersionDataProvider
     * @throws \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     */
    public function testSetPhpVersion(string $version)
    {
        $this->configMock->expects($this->once())
            ->method('set')
            ->with('php.version', $version);

        $this->builder->setPhpVersion($version);
    }

    /**
     * @return array
     */
    public function setPhpVersionDataProvider(): array
    {
        return [
            ['7.0'],
            [IntegrationBuilder::DEFAULT_PHP_VERSION,],
        ];
    }

    /**
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Service php:2 is not supported
     */
    public function testSetPhpWithError()
    {
        $this->configMock->expects($this->never())
            ->method('set');

        $this->builder->setPhpVersion('2');
    }

    /**
     * @param string $version
     * @dataProvider setDbVersionDataProvider
     * @throws \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     */
    public function testSetDbVersion(string $version)
    {
        $this->configMock->expects($this->once())
            ->method('set')
            ->with('db.version', $version);

        $this->builder->setDbVersion($version);
    }

    /**
     * @return array
     */
    public function setDbVersionDataProvider(): array
    {
        return [
            [IntegrationBuilder::DEFAULT_DB_VERSION],
        ];
    }

    /**
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Service db:2 is not supported
     */
    public function testSetDbWithError()
    {
        $this->configMock->expects($this->never())
            ->method('set');

        $this->builder->setDbVersion('2');
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testSetRabbitMQVersion()
    {
        $this->configMock->expects($this->once())
            ->method('set')
            ->with(BuilderInterface::RABBIT_MQ_VERSION, '3.5');

        $this->builder->setRabbitMQVersion('3.5');
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testSetESVersion()
    {
        $this->configMock->expects($this->once())
            ->method('set')
            ->with(BuilderInterface::ES_VERSION, '2.4');

        $this->builder->setESVersion('2.4');
    }

    public function testBuild()
    {
        $serviceMock = $this->getMockForAbstractClass(ServiceInterface::class);
        $serviceMock->method('get')
            ->willReturn([]);

        $config = $this->builder->build();

        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('services', $config);
    }
}
