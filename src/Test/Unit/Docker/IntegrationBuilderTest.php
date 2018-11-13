<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\Docker\BuilderInterface;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use Magento\MagentoCloud\Docker\IntegrationBuilder;
use Magento\MagentoCloud\Filesystem\FileList;
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
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var ServiceFactory|MockObject
     */
    private $serviceFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->serviceFactoryMock = $this->createTestProxy(ServiceFactory::class);

        $this->builder = new IntegrationBuilder(
            $this->fileListMock,
            $this->serviceFactoryMock
        );
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testBuild()
    {
        $config = new Repository([
            BuilderInterface::DB_VERSION => '10.0',
            BuilderInterface::PHP_VERSION => '7.0',
            BuilderInterface::NGINX_VERSION => 'latest'
        ]);

        $this->builder->build($config);
    }

    public function testGetConfigPath()
    {
        $this->fileListMock->expects($this->once())
            ->method('getToolsDockerCompose')
            ->willReturn('/tools/docker-compose.yaml');

        $this->assertSame(
            '/tools/docker-compose.yaml',
            $this->builder->getConfigPath()
        );
    }
}
