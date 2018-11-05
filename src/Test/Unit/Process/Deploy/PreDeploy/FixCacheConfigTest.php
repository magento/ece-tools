<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\FixCacheConfig;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * {@inheritdoc}
 */
class FixCacheConfigTest extends TestCase
{
    use PHPMock;

    /**
     * @var FixCacheConfig
     */
    private $process;

    /**
     * @var ConfigReader|TestCase
     */
    private $readerMock;

    /**
     * @var ConfigWriter|TestCase
     */
    private $writerMock;

    /**
     * @var LoggerInterface|TestCase
     */
    private $loggerMock;

    /**
     * @var MockObject
     */
    private $socketCreateMock;

    /**
     * @var MockObject
     */
    private $socketConnectMock;

    /**
     * @var MockObject
     */
    private $socketCloseMock;

    protected function setUp()
    {
        $this->readerMock = $this->createMock(ConfigReader::class);
        $this->writerMock = $this->createMock(ConfigWriter::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->process = new FixCacheConfig($this->readerMock, $this->writerMock, $this->loggerMock);

        $this->socketCreateMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\PreDeploy',
            'socket_create'
        );
        $this->socketConnectMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\PreDeploy',
            'socket_connect'
        );
        $this->socketCloseMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\PreDeploy',
            'socket_close'
        );
    }

    public function testExecuteWithNoEnv()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);

        $this->writerMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->socketCreateMock->expects($this->never());
        $this->socketConnectMock->expects($this->never());
        $this->socketCloseMock->expects($this->never());

        $this->process->execute();
    }

    public function testExecuteWithoutRedisCache()
    {
        $sampleEnvData = [
            'MAGE_MODE' => 'production',
            'cache_types' => [
                'compiled_config' => 1,
                'config' => 1,
                'layout' => 1,
                'block_html' => 1,
                'collections' => 1,
                'reflection' => 1,
                'db_ddl' => 1,
                'eav' => 1,
                'customer_notification' => 1,
                'full_page' => 1,
                'config_integration' => 1,
                'config_integration_api' => 1,
                'target_rule' => 1,
                'translate' => 1,
                'config_webservice' => 1
            ],
            'backend' => [
                'frontName' => 'admin'
            ],
            'cache' => [
                'frontend' => [
                    'default' => [
                        'backend' => 'Some_Cache_Backend',
                    ],
                    'page_cache' => [
                        'backend' => 'Another_Cache_Backend',
                    ]
                ]
            ],
        ];

        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($sampleEnvData);

        $this->writerMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->socketCreateMock->expects($this->never());
        $this->socketConnectMock->expects($this->never());
        $this->socketCloseMock->expects($this->never());

        $this->process->execute();
    }

    public function testExecuteWithRedis()
    {
        $sampleEnvData = [
            'MAGE_MODE' => 'production',
            'cache_types' => [
                'compiled_config' => 1,
                'config' => 1,
                'layout' => 1,
                'block_html' => 1,
                'collections' => 1,
                'reflection' => 1,
                'db_ddl' => 1,
                'eav' => 1,
                'customer_notification' => 1,
                'full_page' => 1,
                'config_integration' => 1,
                'config_integration_api' => 1,
                'target_rule' => 1,
                'translate' => 1,
                'config_webservice' => 1
            ],
            'backend' => [
                'frontName' => 'admin'
            ],
            'cache' => [
                'frontend' => [
                    'default' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'redis.internal',
                            'port' => 6379,
                            'database' => 1
                        ]
                    ],
                    'page_cache' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'redis.internal',
                            'port' => 6379,
                            'database' => 1
                        ]
                    ]
                ]
            ],
        ];

        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($sampleEnvData);

        $this->socketCreateMock->expects($this->exactly(2))
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->exactly(2))
            ->with('socket resource', 'redis.internal', 6379)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->exactly(2))
            ->with('socket resource');

        $this->writerMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->process->execute();
    }

    public function testExecute()
    {
        $sampleEnvData = [
            'MAGE_MODE' => 'production',
            'cache_types' => [
                'compiled_config' => 1,
                'config' => 1,
                'layout' => 1,
                'block_html' => 1,
                'collections' => 1,
                'reflection' => 1,
                'db_ddl' => 1,
                'eav' => 1,
                'customer_notification' => 1,
                'full_page' => 1,
                'config_integration' => 1,
                'config_integration_api' => 1,
                'target_rule' => 1,
                'translate' => 1,
                'config_webservice' => 1
            ],
            'backend' => [
                'frontName' => 'admin'
            ],
            'cache' => [
                'frontend' => [
                    'default' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'redis.internal',
                            'port' => 6379,
                            'database' => 1
                        ]
                    ],
                    'page_cache' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'redis.internal',
                            'port' => 6379,
                            'database' => 1
                        ]
                    ]
                ]
            ],
        ];

        $expectedEnvData = [
            'MAGE_MODE' => 'production',
            'cache_types' => [
                'compiled_config' => 1,
                'config' => 1,
                'layout' => 1,
                'block_html' => 1,
                'collections' => 1,
                'reflection' => 1,
                'db_ddl' => 1,
                'eav' => 1,
                'customer_notification' => 1,
                'full_page' => 1,
                'config_integration' => 1,
                'config_integration_api' => 1,
                'target_rule' => 1,
                'translate' => 1,
                'config_webservice' => 1
            ],
            'backend' => [
                'frontName' => 'admin'
            ],
        ];

        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($sampleEnvData);
        $this->socketCreateMock->expects($this->once())
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->once())
            ->with('socket resource', 'redis.internal', 6379)
            ->willReturn(false);
        $this->socketCloseMock->expects($this->once())
            ->with('socket resource');
        $this->writerMock->expects($this->once())
            ->method('create')
            ->with($expectedEnvData);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Cache is configured for a Redis service that is not available. Temporarily disabling cache.');

        $this->process->execute();
    }
}
