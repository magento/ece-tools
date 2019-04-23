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

    public function testGetVersions()
    {

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
        $this->version->getServiceVersion(Config::KEY_PHP);
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
                Config::KEY_PHP,
                7.1
            ],
            [
                [
                    'type' => 'php:7.1',
                    'services' => [
                        Config::KEY_ELASTICSEARCH => [
                            'version' => '6.7'
                        ]
                    ]
                ],
                Config::KEY_ELASTICSEARCH,
                6.7
            ],
            [
                [
                    'services' => [
                        Config::KEY_ELASTICSEARCH => [
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
