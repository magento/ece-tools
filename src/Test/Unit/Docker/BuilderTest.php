<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\Builder;
use Magento\MagentoCloud\Docker\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class BuilderTest extends TestCase
{
    /**
     * @var Builder
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

        $this->repositoryFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->configMock);

        $this->builder = new Builder(
            $this->repositoryFactoryMock
        );
    }

    /**
     * @param string $version
     * @dataProvider setNginxVersionDataProvider
     * @throws \Magento\MagentoCloud\Docker\Exception
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
            [Builder::CONFIG_DEFAULT_NGINX_VERSION,],
        ];
    }

    /**
     * @expectedException Exception
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
     * @throws \Magento\MagentoCloud\Docker\Exception
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
            [Builder::CONFIG_DEFAULT_PHP_VERSION,],
        ];
    }

    /**
     * @expectedException Exception
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
     * @throws \Magento\MagentoCloud\Docker\Exception
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
            [Builder::CONFIG_DEFAULT_DB_VERSION],
        ];
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Service db:2 is not supported
     */
    public function testSetDbWithError()
    {
        $this->configMock->expects($this->never())
            ->method('set');

        $this->builder->setDbVersion('2');
    }

    /**
     * @param bool $isCli
     * @param bool $isRo
     * @param string $expected
     * @dataProvider getMagentoVolumeDataProvider
     */
    public function testGetMagentoVolume(bool $isCli, bool $isRo, string $expected)
    {
        $this->configMock->expects($this->any())
            ->method('get')
            ->with('roVolume')
            ->willReturn($isRo);

        $this->assertEquals($expected, $this->builder->getMagentoVolume($isCli));
    }

    public function getMagentoVolumeDataProvider() : array
    {
        return [
            [true, true, "'.:/var/www/magento:rw"],
            [false, true, "'.:/var/www/magento:ro"],
            [false, false, "'.:/var/www/magento:rw"],
            [true, false, "'.:/var/www/magento:rw"]
        ];
    }

    public function testBuild()
    {
        $config = $this->builder->build();

        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('services', $config);
    }
}
