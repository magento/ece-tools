<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\Valkey;
use Magento\MagentoCloud\Service\Valkey\Version;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ValkeyTest extends TestCase
{
    /**
     * @var Valkey
     */
    private $valkey;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var Version|MockObject
     */
    private $versionRetrieverMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->versionRetrieverMock = $this->createMock(Version::class);

        $this->valkey = new Valkey(
            $this->environmentMock,
            $this->versionRetrieverMock
        );
    }

    public function testGetConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Valkey::RELATIONSHIP_KEY)
            ->willReturn(
                [
                [
                'host' => '127.0.0.1',
                'port' => '3306',
                ]
                ]
            );

        $this->assertSame(
            [
            'host' => '127.0.0.1',
            'port' => '3306',
            ],
            $this->valkey->getConfiguration()
        );
    }

    public function testGetSlaveConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Valkey::RELATIONSHIP_SLAVE_KEY)
            ->willReturn(
                [
                [
                'host' => '127.0.0.1',
                'port' => '3307',
                ]
                ]
            );

        $this->assertSame(
            [
            'host' => '127.0.0.1',
            'port' => '3307',
            ],
            $this->valkey->getSlaveConfiguration()
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
            $this->valkey->getVersion()
        );
    }
}
