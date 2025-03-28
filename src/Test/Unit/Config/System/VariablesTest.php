<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\System;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\SystemConfigInterface;
use Magento\MagentoCloud\Config\System\Variables;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;

/**
 * @inheritdoc
 */
class VariablesTest extends TestCase
{
    /**
     * @var Variables
     */
    private $config;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReaderMock;

    /**
     * @var Schema|MockObject
     */
    private $schemaMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->schemaMock = $this->createMock(Schema::class);
        $this->schemaMock->method('getDefaults')
            ->with(SystemConfigInterface::SYSTEM_VARIABLES)
            ->willReturn([
                SystemConfigInterface::VAR_ENV_RELATIONSHIPS => 'MAGENTO_CLOUD_RELATIONSHIPS',
                SystemConfigInterface::VAR_ENV_ROUTES => 'MAGENTO_CLOUD_ROUTES',
                SystemConfigInterface::VAR_ENV_VARIABLES => 'MAGENTO_CLOUD_VARIABLES',
                SystemConfigInterface::VAR_ENV_APPLICATION => 'MAGENTO_CLOUD_APPLICATION',
                SystemConfigInterface::VAR_ENV_ENVIRONMENT => 'MAGENTO_CLOUD_ENVIRONMENT',
            ]);

        $this->config = new Variables(
            $this->environmentReaderMock,
            $this->schemaMock
        );
    }

    /**
     * @param string $name
     * @param array $envConfig
     * @param mixed $expectedValue
     * @dataProvider getDataProvider
     *
     * @throws ConfigException
     */
    public function testGet(string $name, array $envConfig, $expectedValue): void
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([SystemConfigInterface::SYSTEM_VARIABLES => $envConfig]);

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'default relationships' => [
                SystemConfigInterface::VAR_ENV_RELATIONSHIPS,
                [],
                'MAGENTO_CLOUD_RELATIONSHIPS',
            ],
            'default routes' => [
                SystemConfigInterface::VAR_ENV_ROUTES,
                [],
                'MAGENTO_CLOUD_ROUTES',
            ],
            'default variables' => [
                SystemConfigInterface::VAR_ENV_VARIABLES,
                [],
                'MAGENTO_CLOUD_VARIABLES',
            ],
            'default application' => [
                SystemConfigInterface::VAR_ENV_APPLICATION,
                [],
                'MAGENTO_CLOUD_APPLICATION',
            ],
            'default environment' => [
                SystemConfigInterface::VAR_ENV_ENVIRONMENT,
                [],
                'MAGENTO_CLOUD_ENVIRONMENT',
            ],
        ];
    }

    /**
     * @throws ConfigException
     */
    public function testNotExists(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Config NOT_EXISTS_VALUE was not defined.');

        $this->environmentReaderMock->method('read')
            ->willReturn([]);

        $this->config->get('NOT_EXISTS_VALUE');
    }

    /**
     * @throws ConfigException
     */
    public function testGetWithFileSystemException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('some error message');

        $this->environmentReaderMock->method('read')
            ->willThrowException(new FileSystemException('some error message'));

        $this->config->get(SystemConfigInterface::VAR_ENV_ROUTES);
    }
}
