<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\EnvironmentDataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
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
    protected function setUp()
    {
        $this->environmentDataMock = $this->createMock(EnvironmentDataInterface::class);

        $this->environment = new Environment($this->environmentDataMock);
    }

    public function testGetEnv()
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getEnv')
            ->with('some-key')
            ->willReturn('some-value');

        $this->assertEquals('some-value', $this->environment->getEnv('some-key'));
    }

    /**
     * @param bool $expectedResult
     * @param string $branchName
     * @dataProvider isMasterBranchDataProvider
     */
    public function testIsMasterBranch(bool $expectedResult, string $branchName)
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
     * @return array
     */
    public function isMasterBranchDataProvider(): array
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
        ];
    }
}
