<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\Valkey\Version;
use Magento\MagentoCloud\Service\ValkeySession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
#[AllowMockObjectsWithoutExpectations]
class ValkeySessionTest extends TestCase
{
    /**
     * @var ValkeySession
     */
    private $valkeySession;

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

        $this->valkeySession = new ValkeySession($this->environmentMock, $this->versionRetrieverMock);
    }

    public function testGetConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
          ->method('getRelationship')
          ->with(ValkeySession::RELATIONSHIP_SESSION_KEY)
          ->willReturn([['host' => '127.0.0.1', 'port' => '3306',]]);

        $this->assertSame(['host' => '127.0.0.1', 'port' => '3306',], $this->valkeySession->getConfiguration());
    }

    public function testGetVersion(): void
    {
        $version = '1.1.1';
        $config = [['some config']];

        $this->environmentMock->expects($this->once())->method('getRelationship')->willReturn($config);

        $this->versionRetrieverMock->expects($this->once())
          ->method('getVersion')
          ->with($config[0])
          ->willReturn($version);
        $this->assertSame($version, $this->valkeySession->getVersion());
    }
}
