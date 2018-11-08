<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker\Config;

use Magento\MagentoCloud\Docker\Config\Reader;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ReaderTest extends TestCase
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileListMock = $this->createMock(FileList::class);

        $this->reader = new Reader(
            $this->fileListMock
        );
    }

    /**
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     * @expectedExceptionMessage PHP version could not be parsed.
     *
     * @throws FileSystemException
     */
    public function testReadEmpty()
    {
        $this->fileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/empty/.magento.app.yaml');
        $this->fileListMock->expects($this->once())
            ->method('getServicesConfig')
            ->willReturn(__DIR__ . '/_files/empty/services.yaml');

        $this->reader->read();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     * @expectedExceptionMessage Relationships could not be parsed.
     *
     * @throws FileSystemException
     */
    public function testReadWithPhp()
    {
        $this->fileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/with_php/.magento.app.yaml');
        $this->fileListMock->expects($this->once())
            ->method('getServicesConfig')
            ->willReturn(__DIR__ . '/_files/with_php/services.yaml');

        $this->reader->read();
    }

    /**
     * @expectedExceptionMessage Only one instance of service "elasticsearch" supported
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     *
     * @throws FileSystemException
     */
    public function testReadWithMultipleSameServices()
    {
        $this->fileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/with_multiple/.magento.app.yaml');
        $this->fileListMock->expects($this->once())
            ->method('getServicesConfig')
            ->willReturn(__DIR__ . '/_files/with_multiple/services.yaml');

        $this->assertSame([
            'type' => 'php:7.1',
            'services' => [
                'mysql' => [
                    'service' => 'mysql',
                    'version' => '10.0'
                ],
                'redis' => [
                    'service' => 'redis',
                    'version' => '3.0'
                ],
                'elasticsearch' => [
                    'service' => 'elasticsearch',
                    'version' => '1.4'
                ],
                'rabbitmq' => [
                    'service' => 'rabbitmq',
                    'version' => '3.5'
                ]
            ]
        ], $this->reader->read());
    }

    /**
     * @expectedExceptionMessage Service with name "myrabbitmq" could not be parsed
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     *
     * @throws FileSystemException
     */
    public function testReadWithMissedService()
    {
        $this->fileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/with_missed_service/.magento.app.yaml');
        $this->fileListMock->expects($this->once())
            ->method('getServicesConfig')
            ->willReturn(__DIR__ . '/_files/with_missed_service/services.yaml');

        $this->assertSame([
            'type' => 'php:7.1',
            'services' => [
                'mysql' => [
                    'service' => 'mysql',
                    'version' => '10.0'
                ],
                'redis' => [
                    'service' => 'redis',
                    'version' => '3.0'
                ],
                'elasticsearch' => [
                    'service' => 'elasticsearch',
                    'version' => '1.4'
                ],
                'rabbitmq' => [
                    'service' => 'rabbitmq',
                    'version' => '3.5'
                ]
            ]
        ], $this->reader->read());
    }

    /**
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     * @expectedExceptionMessage Some error
     *
     * @throws FileSystemException
     */
    public function testReadBroken()
    {
        $this->fileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willThrowException(new \Exception('Some error'));

        $this->reader->read();
    }

    /**
     * @throws FileSystemException
     */
    public function testRead()
    {
        $this->fileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/.magento.app.yaml');
        $this->fileListMock->expects($this->once())
            ->method('getServicesConfig')
            ->willReturn(__DIR__ . '/_files/services.yaml');

        $this->assertSame([
            'type' => 'php:7.1',
            'services' => [
                'mysql' => [
                    'service' => 'mysql',
                    'version' => '10.0'
                ],
                'redis' => [
                    'service' => 'redis',
                    'version' => '3.0'
                ],
                'elasticsearch' => [
                    'service' => 'elasticsearch',
                    'version' => '1.4'
                ],
                'rabbitmq' => [
                    'service' => 'rabbitmq',
                    'version' => '3.5'
                ]
            ]
        ], $this->reader->read());
    }
}
