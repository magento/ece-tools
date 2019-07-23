<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker\Config;

use Magento\MagentoCloud\Docker\Config\Reader;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @inheritDoc
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
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->fileListMock->method('getAppConfig')
            ->willReturn('/root/.magento.app.yaml');
        $this->fileListMock->method('getServicesConfig')
            ->willReturn('/root/.magento/services.yaml');

        $this->reader = new Reader(
            $this->fileListMock,
            $this->fileMock
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
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->willReturn(Yaml::dump([]));

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
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->willReturnMap([
                ['/root/.magento.app.yaml', false, null, Yaml::dump(['type' => 'php:7.1'])],
                ['/root/.magento/services.yaml', false, null, Yaml::dump([])]
            ]);

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
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->willReturnMap([
                [
                    '/root/.magento.app.yaml',
                    false,
                    null,
                    Yaml::dump([
                        'type' => 'php:7.1',
                        'relationships' => [
                            'database' => 'mysql:mysql',
                            'elasticsearch' => 'elasticsearch:elasticsearch',
                            'elasticsearch5' => 'elasticsearch5:elasticsearch'
                        ]
                    ]),
                ],
                [
                    '/root/.magento/services.yaml',
                    false,
                    null,
                    Yaml::dump([
                        'mysql' => [
                            'type' => 'mysql:10.0',
                            'disk' => '2048'
                        ],
                        'elasticsearch' => [
                            'type' => 'elasticsearch:1.4',
                            'disk' => '1024'
                        ],
                        'elasticsearch5' => [
                            'type' => 'elasticsearch:5.2',
                            'disk' => '1024'
                        ]
                    ])
                ]
            ]);

        $this->assertSame([
            'type' => 'php:7.1',
            'crons' => [],
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
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->willReturnMap([
                [
                    '/root/.magento.app.yaml',
                    false,
                    null,
                    Yaml::dump([
                        'type' => 'php:7.1',
                        'relationships' => [
                            'database' => 'mysql:mysql',
                            'elasticsearch' => 'elasticsearch:elasticsearch',
                            'mq' => 'myrabbitmq:rabbitmq'
                        ]
                    ]),
                ],
                [
                    '/root/.magento/services.yaml',
                    false,
                    null,
                    Yaml::dump([
                        'mysql' => [
                            'type' => 'mysql:10.0',
                            'disk' => '2048'
                        ],
                        'elasticsearch' => [
                            'type' => 'elasticsearch:1.4',
                            'disk' => '1024'
                        ],
                    ])
                ]
            ]);

        $this->reader->read();
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
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->willReturnMap([
                [
                    '/root/.magento.app.yaml',
                    false,
                    null,
                    Yaml::dump([
                        'type' => 'php:7.1',
                        'relationships' => [
                            'database' => 'mysql:mysql',
                            'redis' => 'redis:redis',
                            'elasticsearch' => 'elasticsearch:elasticsearch',
                            'mq' => 'myrabbitmq:rabbitmq'
                        ],
                    ]),
                ],
                [
                    '/root/.magento/services.yaml',
                    false,
                    null,
                    Yaml::dump([
                        'mysql' => [
                            'type' => 'mysql:10.0',
                            'disk' => '2048'
                        ],
                        'redis' => [
                            'type' => 'redis:3.0'
                        ],
                        'elasticsearch' => [
                            'type' => 'elasticsearch:1.4',
                            'disk' => '1024'
                        ],
                        'myrabbitmq' => [
                            'type' => 'rabbitmq:3.5',
                            'disk' => '1024'
                        ]
                    ])
                ]
            ]);

        $this->assertSame([
            'type' => 'php:7.1',
            'crons' => [],
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
            ],
            'runtime' => [
                'extensions' => [],
                'disabled_extensions' => [],
            ]
        ], $this->reader->read());
    }
}
