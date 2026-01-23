<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Service\Aurora;
use Magento\MagentoCloud\Service\ServiceException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
#[AllowMockObjectsWithoutExpectations]
class AuroraTest extends TestCase
{
    /**
     * @var Aurora
     */
    private $aurora;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->connectionMock = $this->createMock(ConnectionInterface::class);

        $this->aurora = new Aurora($this->connectionMock);
    }

    /**
     * Test get configuration method.
     *
     * @return void
     */
    public function testGetConfiguration(): void
    {
        $this->assertSame(
            [],
            $this->aurora->getConfiguration()
        );
    }

    /**
     * Test get version method.
     *
     * @param array $version
     * @param string $expectedResult
     * @dataProvider getVersionDataProvider
     * @return void
     * @throws ServiceException
     */
    #[DataProvider('getVersionDataProvider')]
    public function testGetVersion(array $version, string $expectedResult): void
    {
        $this->connectionMock->expects($this->once())
            ->method('selectOne')
            ->with('SELECT AURORA_VERSION() as version')
            ->willReturn($version);

        $this->assertEquals($expectedResult, $this->aurora->getVersion());
    }

    /**
     * Data provider for get version method.
     *
     * @return array
     */
    public static function getVersionDataProvider(): array
    {
        return [
            [
                [
                    'version' => '2.07.2'
                ],
                '2.07'
            ],
            [
                [
                    'version' => '1.0.16'
                ],
                '1.0'
            ],
            [
                [],
                '0'
            ],
            [
                [
                    'version' => ''
                ],
                '0'
            ],
        ];
    }
}
