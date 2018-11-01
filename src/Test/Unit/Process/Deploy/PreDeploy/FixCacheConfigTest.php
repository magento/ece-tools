<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\FixCacheConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * {@inheritdoc}
 */
class FixCacheConfigTest extends TestCase
{
    /**
     * @var FixCacheConfig
     */
    private $process;

    /**
     * @var Environment|TestCase
     */
    private $environmentMock;

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

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->readerMock = $this->createMock(ConfigReader::class);
        $this->writerMock = $this->createMock(ConfigWriter::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->process = new FixCacheConfig(
            $this->environmentMock,
            $this->readerMock,
            $this->writerMock,
            $this->loggerMock
        );
    }

    public function testExecuteWithRedisRelationship()
    {
        $relationshipArray = $this->getSampleRelationships();
        $relationshipArray['redis'] = [[
            'service' => 'redis',
            'host' => 'redis.internal',
            'port' => 6379,
        ]];

        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn($relationshipArray);

        $this->readerMock->expects($this->never())
            ->method('read');
        $this->writerMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->process->execute();
    }

    public function testExecuteWithNoEnv()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn($this->getSampleRelationships());
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);

        $this->writerMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->never())
            ->method('notice');

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

        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn($this->getSampleRelationships());
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($sampleEnvData);

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

        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn($this->getSampleRelationships());
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($sampleEnvData);
        $this->writerMock->expects($this->once())
            ->method('create')
            ->with($expectedEnvData);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Cache is configured for Redis but no Redis service is available. Temporarily disabling cache.');

        $this->process->execute();
    }

    private function getSampleRelationships(): array
    {
        return [
            'database' => [[
                'username' => 'user',
                'password' => '',
                'path' => 'main',
                'service' => 'mysql',
                'host' => 'database.internal',
                'port' => 3306,
            ]],
        ];
    }
}
