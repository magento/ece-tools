<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\EnvironmentDataInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class EnvironmentTest extends TestCase
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var EnvironmentDataInterface|MockObject
     */
    private $environmentDataMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentDataMock = $this->createMock(EnvironmentDataInterface::class);

        $this->environment = new Environment($this->environmentDataMock);
    }

    /**
     * Test getEnv method.
     *
     * @return void
     */
    public function testGetEnv(): void
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getEnv')
            ->with('some-key')
            ->willReturn('some-value');

        $this->assertEquals('some-value', $this->environment->getEnv('some-key'));
    }

    /**
     * Test isMasterBranch method.
     *
     * @param bool $expectedResult
     * @param string $branchName
     * @param void
     * @dataProvider isMasterBranchDataProvider
     */
    #[DataProvider('isMasterBranchDataProvider')]
    public function testIsMasterBranch(bool $expectedResult, string $branchName): void
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getBranchName')
            ->willReturn($branchName);

        $this->assertSame(
            $expectedResult,
            $this->environment->isMasterBranch()
        );
    }

    /**
     * Data provider for testIsMasterBranch.
     *
     * @return array
     */
    public static function isMasterBranchDataProvider(): array
    {
        return [
            [false, 'branch213'],
            [false, 'prod-branch'],
            [false, 'stage'],
            [false, 'branch-production-lad13m'],
            [false, 'branch-staging-lad13m'],
            [false, 'branch-master-lad13m'],
            [false, 'branch-production'],
            [false, 'branch-staging'],
            [false, 'branch-master'],
            [false, 'product'],
            [true, 'staging'],
            [true, 'staging-ba3ma'],
            [true, 'master'],
            [true, 'master-ad123m'],
            [true, 'production'],
            [true, 'production-lad13m'],
            [true, 'staging-3'],
            [true, 'staging3'],
            [true, 'master3'],
            [true, 'mastertest'],
            [true, 'staging3-nf5kgsq'],
        ];
    }

    /**
     * Test getCryptKey method.
     *
     * @return void
     */
    public function testGetCryptKey(): void
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getVariables')
            ->willReturn(['CRYPT_KEY' => 'secret-key']);

        $this->assertSame('secret-key', $this->environment->getCryptKey());
    }

    /**
     * Test getApplication method.
     *
     * @return void
     */
    public function testGetApplication(): void
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getApplication')
            ->willReturn(['some' => 'value']);

        $this->assertSame(['some' => 'value'], $this->environment->getApplication());
    }

    /**
     * Test getRoutes method.
     *
     * @return void
     */
    public function testGetRoutes(): void
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn(['some' => 'routes']);

        $this->assertSame(['some' => 'routes'], $this->environment->getRoutes());
    }

    /**
     * Test getRelationships method.
     *
     * @return void
     */
    public function testGetRelationships(): void
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(['some' => 'relationships']);

        $this->assertSame(['some' => 'relationships'], $this->environment->getRelationships());
    }

    /**
     * Test getRelationship method.
     *
     * @return void
     */
    public function testGetRelationship(): void
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(['some' => ['relationships' => ['redis','valkey', 'mysql']]]);

        $this->assertSame(
            ['relationships' => ['redis','valkey', 'mysql']],
            $this->environment->getRelationship('some')
        );
    }

    /**
     * Test getEnvVarMageErrorReportDirNestingLevel method.
     *
     * @return void
     */
    public function testGetEnvVarMageErrorReportDirNestingLevel(): void
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getEnv')
            ->with('MAGE_ERROR_REPORT_DIR_NESTING_LEVEL')
            ->willReturn(1);

        $this->assertSame(1, $this->environment->getEnvVarMageErrorReportDirNestingLevel());
    }

    /**
     * Test hasMount method.
     *
     * @return void
     */
    public function testHasMount(): void
    {
        $this->environmentDataMock->method('getApplication')
            ->willReturn([
                'mounts' => [
                    'test' => [],
                    '/test_with_slash' => []
                ]
            ]);

        self::assertTrue($this->environment->hasMount('test'));
        self::assertTrue($this->environment->hasMount('/test'));
        self::assertTrue($this->environment->hasMount('test_with_slash'));
        self::assertTrue($this->environment->hasMount('/test_with_slash'));
        self::assertFalse($this->environment->hasMount('/unknown'));
        self::assertFalse($this->environment->hasMount('unknown'));
    }
}
