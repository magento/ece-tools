<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker\Service;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Docker\Config\Reader;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Illuminate\Config\Repository;
use Magento\MagentoCloud\Service\ServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private $version;

    /**
     * @var Reader|MockObject
     */
    private $readerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->readerMock = $this->createMock(Reader::class);

        $this->version = new Config($this->readerMock);
    }

    public function testGetAllServiceVersions()
    {
        $customVersions = [
            ServiceInterface::NAME_DB => 'db.version1',
            ServiceInterface::NAME_ELASTICSEARCH => 'es.version1',
        ];
        $configVersions = [
            'services' => [
                ServiceInterface::NAME_ELASTICSEARCH => ['version' => 'es.version2'],
                ServiceInterface::NAME_RABBITMQ => ['version' => 'rabbitmq.version2'],
            ],
            'type' => 'php:7.0',
        ];
        $result = [
            ServiceInterface::NAME_DB => 'db.version1',
            ServiceInterface::NAME_ELASTICSEARCH => 'es.version1',
            ServiceInterface::NAME_RABBITMQ => 'rabbitmq.version2',
            ServiceInterface::NAME_PHP => '7.0'

        ];
        $customConfigs = new Repository($customVersions);

        $this->readerMock->expects($this->any())
            ->method('read')
            ->willReturn($configVersions);

        $this->assertEquals($result, $this->version->getAllServiceVersions($customConfigs));
    }

    /**
     * @param array $config
     * @param string $serviceName
     * @param string|null $result
     * @throws ConfigurationMismatchException
     *
     * @dataProvider getServiceVersionFromConfigDataProvider
     */
    public function testGetServiceVersionFromConfig(array $config, string $serviceName, $result)
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->assertEquals($result, $this->version->getServiceVersion($serviceName));
    }

    /**
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Type "notphp" is not supported
     */
    public function testGetServiceVersionFromConfigException()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn(['type' => 'notphp:1']);
        $this->version->getServiceVersion(ServiceInterface::NAME_PHP);
    }

    /**
     * @throws ConfigurationMismatchException
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     */
    public function testGetServiceVersionException()
    {
        $exception = new FileSystemException('reader exception');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willThrowException($exception);
        $this->version->getServiceVersion(ServiceInterface::NAME_RABBITMQ);
    }

    /**
     * @param array $config
     * @param string $result
     * @throws \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     *
     * @dataProvider getPhpVersionDataProvider
     */
    public function testGetPhpVersion(array $config, string $result)
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->assertEquals($result, $this->version->getPhpVersion());
    }

    /**
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Some exception
     */
    public function testGetPhpVersionReaderException()
    {
        $exception = new ConfigurationMismatchException('Some exception');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willThrowException($exception);
        $this->version->getPhpVersion();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Type "notphp" is not supported
     */
    public function testGetPhpVersionWrongType()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn(['type' => 'notphp:7.1']);
        $this->version->getPhpVersion();
    }

    /**
     * @throws ConfigurationMismatchException
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     */
    public function testGetPhpVersionException()
    {
        $exception = new FileSystemException('reader exception');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willThrowException($exception);
        $this->version->getPhpVersion();
    }

    /**
     * @param array $config
     * @param string $result
     * @throws ConfigurationMismatchException
     *
     * @dataProvider getCronDataProvider
     */
    public function testGetCron($config, $result)
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->assertEquals($result, $this->version->getCron());
    }

    /**
     * @throws ConfigurationMismatchException
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     */
    public function testGetCronException()
    {
        $exception = new FileSystemException('reader exception');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willThrowException($exception);
        $this->version->getCron();
    }

    /**
     * Data provicer for testGetCron
     *
     * @return array
     */
    public function getCronDataProvider()
    {
        $cronData = ['some cron data'];

        return [
            [
                ['crons' => $cronData],
                $cronData
            ],
            [
                ['notCron' => 'some data'],
                []
            ],
        ];
    }

    public function getPhpVersionDataProvider(): array
    {
        return [
            [
                ['type' => 'php:7.1'],
                '7.1'
            ],
            [
                ['type' => 'php:7.3.0-rc'],
                '7.3.0'
            ],
        ];
    }

    /**
     * @return array
     */
    public function getServiceVersionFromConfigDataProvider(): array
    {
        return [
            [
                ['type' => 'php:7.1'],
                ServiceInterface::NAME_PHP,
                7.1
            ],
            [
                [
                    'type' => 'php:7.1',
                    'services' => [
                        ServiceInterface::NAME_ELASTICSEARCH => [
                            'version' => '6.7'
                        ]
                    ]
                ],
                ServiceInterface::NAME_ELASTICSEARCH,
                6.7
            ],
            [
                [
                    'services' => [
                        ServiceInterface::NAME_ELASTICSEARCH => [
                            'version' => '6.7'
                        ]
                    ]
                ],
                'nonexistent',
                null
            ],
        ];
    }
}
