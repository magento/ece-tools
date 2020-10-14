<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\RemoteStorage;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use PHPUnit\Framework\TestCase;

/**
 * @see RemoteStorage
 */
class RemoteStorageTest extends TestCase
{
    /**
     * @var RemoteStorage
     */
    private $config;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $stageConfig = $this->getMockForAbstractClass(DeployInterface::class);
        $this->config = new RemoteStorage(
            $stageConfig
        );

        $stageConfig->method('get')
            ->with(DeployInterface::VAR_REMOTE_STORAGE)
            ->willReturn([
                'adapter' => 'adapter',
                'prefix' => 'prefix',
                'config' => [
                    'bucket' => 'bucket',
                    'region' => 'region',
                    'key' => 'key',
                    'secret' => 'secret'
                ]
            ]);
    }

    public function testAll(): void
    {
        self::assertSame('adapter', $this->config->getAdapter());
        self::assertSame('prefix', $this->config->getPrefix());
        self::assertSame(
            ['bucket' => 'bucket', 'region' => 'region', 'key' => 'key', 'secret' => 'secret'],
            $this->config->getConfig()
        );
    }
}
