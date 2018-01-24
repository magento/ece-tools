<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Redis;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class RedisTest extends TestCase
{
    /**
     * @var Redis
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->setMethods(['getRelationships', 'getAdminUrl', 'getVariable'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new Redis(
            $this->environmentMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->stageConfigMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php Redis cache configuration.');
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([
                'redis' => [
                    0 => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                    ],
                ],
            ]);
        $this->environmentMock->expects($this->any())
            ->method('getAdminUrl')
            ->willReturn('admin');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);

        $this->configWriterMock->expects($this->once())
            ->method('write')
            ->with([
                'cache' => [
                    'frontend' => [
                        'default' => [
                            'backend' => 'Cm_Cache_Backend_Redis',
                            'backend_options' => [
                                'server' => '127.0.0.1',
                                'port' => '6379',
                                'database' => 1,
                            ],
                        ],
                        'page_cache' => [
                            'backend' => 'Cm_Cache_Backend_Redis',
                            'backend_options' => [
                                'server' => '127.0.0.1',
                                'port' => '6379',
                                'database' => 1,
                            ],
                        ],
                    ],
                ],
                'session' => [
                    'save' => 'redis',
                    'redis' => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                        'database' => 0,
                    ],
                ],
            ]);

        $this->process->execute();
    }

    public function testExecuteRemovingRedis()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Removing redis cache and session configuration from env.php.');
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([]);
        $this->environmentMock->expects($this->any())
            ->method('getAdminUrl')
            ->willReturn('admin');

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'cache' => [
                    'frontend' => [
                        'default' => [
                            'backend' => 'Cm_Cache_Backend_Redis',
                            'backend_options' => [
                                'server' => '127.0.0.1',
                                'port' => '6379',
                                'database' => 1,
                            ],
                        ],
                        'page_cache' => [
                            'backend' => 'Cm_Cache_Backend_Redis',
                            'backend_options' => [
                                'server' => '127.0.0.1',
                                'port' => '6379',
                                'database' => 1,
                            ],
                        ],
                    ],
                ],
                'session' => [
                    'save' => 'redis',
                    'redis' => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                        'database' => 0,
                    ],
                ],
            ]);

        $this->configWriterMock->expects($this->once())
            ->method('write')
            ->with([
                'cache' => [
                    'frontend' => [],
                ],
                'session' => [
                    'save' => 'db',
                ],
            ]);

        $this->process->execute();
    }

    public function testExecuteWithDifferentRedisOptions()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php Redis cache configuration.');
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([
                'redis' => [
                    0 => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                    ],
                ],
            ]);
        $this->environmentMock->expects($this->any())
            ->method('getAdminUrl')
            ->willReturn('admin');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'session' => [
                    'redis' => [
                        'max_concurrency' => 10,
                        'bot_first_lifetime' => 100,
                        'bot_lifetime' => 10000,
                        'min_lifetime' => 100,
                        'max_lifetime' => 10000,
                    ],
                ],
            ]);

        $this->configWriterMock->expects($this->once())
            ->method('write')
            ->with([
                'cache' => [
                    'frontend' => [
                        'default' => [
                            'backend' => 'Cm_Cache_Backend_Redis',
                            'backend_options' => [
                                'server' => '127.0.0.1',
                                'port' => '6379',
                                'database' => 1,
                            ],
                        ],
                        'page_cache' => [
                            'backend' => 'Cm_Cache_Backend_Redis',
                            'backend_options' => [
                                'server' => '127.0.0.1',
                                'port' => '6379',
                                'database' => 1,
                            ],
                        ],
                    ],
                ],
                'session' => [
                    'save' => 'redis',
                    'redis' => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                        'database' => 0,
                        'max_concurrency' => 10,
                        'bot_first_lifetime' => 100,
                        'bot_lifetime' => 10000,
                        'min_lifetime' => 100,
                        'max_lifetime' => 10000,
                    ],
                ],
            ]);

        $this->process->execute();
    }

    public function testRemoveDisableLocking()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    'Updating env.php Redis cache configuration.',
                ],
                [
                    'Removing disable_locking configuration.'
                ]
            );
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([
                'redis' => [
                    0 => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                    ],
                ],
            ]);
        $this->environmentMock->expects($this->any())
            ->method('getAdminUrl')
            ->willReturn('admin');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'session' => [
                    'redis' => [
                        'max_concurrency' => 10,
                        'bot_first_lifetime' => 100,
                        'bot_lifetime' => 10000,
                        'min_lifetime' => 100,
                        'max_lifetime' => 10000,
                        'disable_locking' => '1',
                    ],
                ],
            ]);

        $this->configWriterMock->expects($this->once())
            ->method('write')
            ->with([
                'cache' => [
                    'frontend' => [
                        'default' => [
                            'backend' => 'Cm_Cache_Backend_Redis',
                            'backend_options' => [
                                'server' => '127.0.0.1',
                                'port' => '6379',
                                'database' => 1,
                            ],
                        ],
                        'page_cache' => [
                            'backend' => 'Cm_Cache_Backend_Redis',
                            'backend_options' => [
                                'server' => '127.0.0.1',
                                'port' => '6379',
                                'database' => 1,
                            ],
                        ],
                    ],
                ],
                'session' => [
                    'save' => 'redis',
                    'redis' => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                        'database' => 0,
                        'max_concurrency' => 10,
                        'bot_first_lifetime' => 100,
                        'bot_lifetime' => 10000,
                        'min_lifetime' => 100,
                        'max_lifetime' => 10000,
                    ],
                ],
            ]);

        $this->process->execute();
    }
}
