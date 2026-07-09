<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Credis_Client;
use Magento\MagentoCloud\Config\Factory\Cache as CacheConfig;
use Magento\MagentoCloud\Service\Adapter\CvalkeyFactory;
use Magento\MagentoCloud\Service\Valkey as ValkeyService;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\CleanValkeyCache;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class CleanValkeyCacheTest extends TestCase
{
    /**
     * @var CleanValkeyCache
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var CacheConfig|MockObject
     */
    private $cacheConfigMock;

    /**
     * @var CvalkeyFactory|MockObject
     */
    private $cvalkeyFactoryMock;

    /**
     * @var ValkeyService|MockObject
     */
    private $valkeyServiceMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->cacheConfigMock = $this->createMock(CacheConfig::class);
        $this->cvalkeyFactoryMock = $this->createMock(CvalkeyFactory::class);
        $this->valkeyServiceMock = $this->createMock(ValkeyService::class);
        $this->valkeyServiceMock->method('getConfiguration')->willReturn(['host' => 'cache']);

        $this->step = new CleanValkeyCache(
            $this->loggerMock,
            $this->cacheConfigMock,
            $this->cvalkeyFactoryMock,
            $this->valkeyServiceMock
        );
    }

    public function testExecuteUsesRemoteBackendOptionsForSymfonyL2(): void
    {
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => CacheConfig::VALKEY_BACKEND_SYMFONY_L2,
                        'backend_options' => [
                            'remote_backend' => 'valkey',
                            'remote_backend_options' => [
                                'server' => 'cache',
                                'port' => 6379,
                                'database' => 1,
                                'password' => 'secret',
                            ],
                            'local_backend' => 'file',
                            'local_backend_options' => [
                                'cache_dir' => '/dev/shm/magento_l1',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clearing valkey cache: default');

        /** @var Credis_Client|MockObject $client */
        $client = $this->getMockBuilder(Credis_Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connect', '__call'])
            ->getMock();

        $this->cvalkeyFactoryMock->expects($this->once())
            ->method('create')
            ->with('cache', 6379, 1, 'secret')
            ->willReturn($client);

        $client->expects($this->once())->method('connect');
        $client->expects($this->once())
            ->method('__call')
            ->with('flushDb', [])
            ->willReturn(null);

        $this->step->execute();
    }
}
