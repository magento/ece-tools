<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\Redis\Version;
use Magento\MagentoCloud\Service\RedisSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class RedisSessionTest extends TestCase
{
    /**
     * @var RedisSession
     */
    private $redisSession;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var Version|MockObject
     */
    private $versionRetrieverMock;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->versionRetrieverMock = $this->createMock(Version::class);

        $this->redisSession = new RedisSession(
            $this->environmentMock,
            $this->versionRetrieverMock
        );
    }

    public function testGetConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(RedisSession::RELATIONSHIP_SESSION_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3306',
            ],
            $this->redisSession->getConfiguration()
        );
    }

    public function testGetVersion(): void
    {
        $version = '1.1.1';
        $config = [['some config']];

        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->willReturn($config);

        $this->versionRetrieverMock->expects($this->once())
            ->method('getVersion')
            ->with($config[0])
            ->willReturn($version);
        $this->assertSame(
            $version,
            $this->redisSession->getVersion()
        );
    }
}
