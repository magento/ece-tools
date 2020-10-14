<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RemoteStorage;
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
        $environmentMock = $this->createMock(Environment::class);
        $this->config = new RemoteStorage(
            $environmentMock
        );

        $environmentMock->method('getEnv')
            ->willReturnMap([
                ['REMOTE_STORAGE_ADAPTER', 'adapter'],
                ['REMOTE_STORAGE_PREFIX', 'prefix'],
                ['REMOTE_STORAGE_BUCKET', 'bucket'],
                ['REMOTE_STORAGE_REGION', 'region'],
                ['REMOTE_STORAGE_KEY', 'key'],
                ['REMOTE_STORAGE_SECRET', 'secret']
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
