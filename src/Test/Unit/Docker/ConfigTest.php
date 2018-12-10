<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker;

use Magento\MagentoCloud\Docker\Config;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
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
    private $config;

    /**
     * @var Config\Reader|MockObject
     */
    private $readerMock;

    protected function setUp()
    {
        $this->readerMock = $this->createMock(Config\Reader::class);

        $this->config = new Config(
            $this->readerMock
        );
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testGetServiceVersion()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'services' => [
                    Config::KEY_DB => [
                        'version' => '10.0'
                    ]
                ]
            ]);

        $this->assertSame('10.0', $this->config->getServiceVersion(Config::KEY_DB));
    }

    /**
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     *
     * @throws ConfigurationMismatchException
     */
    public function testGetServiceVersionWithException()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willThrowException(new FileSystemException('Some error'));

        $this->assertSame('10.0', $this->config->getServiceVersion(Config::KEY_DB));
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testGetPhpVersion()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'type' => 'php:7.0-rc'
            ]);

        $this->assertSame('7.0', $this->config->getPhpVersion());
    }

    /**
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Type "ruby" is not supported
     *
     * @throws ConfigurationMismatchException
     */
    public function testGetPhpVersionBroken()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'type' => 'ruby:2.0'
            ]);

        $this->config->getPhpVersion();
    }

    /**
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     *
     * @throws ConfigurationMismatchException
     */
    public function testGetPhpVersionWithReadException()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willThrowException(new FileSystemException('Some error'));

        $this->config->getPhpVersion();
    }
}
