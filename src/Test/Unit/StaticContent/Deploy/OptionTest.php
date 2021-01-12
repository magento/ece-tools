<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\StaticContent\Deploy;

use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Connection;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\Deploy\Option;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class OptionTest extends TestCase
{
    /**
     * @var Option
     */
    private $option;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var AdminDataInterface|MockObject
     */
    private $adminDataMock;

    /**
     * @var Connection|MockObject
     */
    private $connectionMock;

    /**
     * @var ThreadCountOptimizer|MockObject
     */
    private $threadCountOptimizerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->connectionMock = $this->createMock(Connection::class);
        $this->adminDataMock = $this->getMockForAbstractClass(AdminDataInterface::class);
        $this->threadCountOptimizerMock = $this->createMock(ThreadCountOptimizer::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->option = new Option(
            $this->adminDataMock,
            $this->connectionMock,
            $this->magentoVersionMock,
            $this->threadCountOptimizerMock,
            $this->stageConfigMock
        );
    }

    public function testGetThreadCount(): void
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_SCD_STRATEGY, 'strategyName'],
                [DeployInterface::VAR_SCD_THREADS, 3],
            ]);
        $this->threadCountOptimizerMock->expects($this->once())
            ->method('optimize')
            ->with(3, 'strategyName')
            ->willReturn(3);

        $this->assertEquals(3, $this->option->getThreadCount());
    }

    /**
     * Test getting the SCD strategy from the strategy checker.
     */
    public function testGetStrategy(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_SCD_STRATEGY]
            )
            ->willReturn(
                'strategy',
                ['strategy']
            );

        $this->assertEquals('strategy', $this->option->getStrategy());
    }

    public function testIsForce(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $this->assertTrue($this->option->isForce());
    }

    public function testGetLocales(): void
    {
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with(
                "SELECT `value` FROM `core_config_data` WHERE `path`='general/locale/code' " .
                "UNION SELECT `interface_locale` FROM `admin_user`"
            )
            ->willReturn([
                ['value' => 'fr_FR'],
                ['value' => 'de_DE'],
            ]);
        $this->connectionMock->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturnArgument(0);
        $this->adminDataMock->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturn('en_US');

        $this->assertEquals(
            [
                'fr_FR',
                'de_DE',
                'en_US',
            ],
            $this->option->getLocales()
        );
    }

    public function testGetLocalesWithExistsAdminLocale(): void
    {
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with(
                "SELECT `value` FROM `core_config_data` WHERE `path`='general/locale/code' " .
                "UNION SELECT `interface_locale` FROM `admin_user`"
            )
            ->willReturn([
                ['value' => 'fr_FR'],
                ['value' => 'de_DE'],
            ]);
        $this->connectionMock->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturnArgument(0);
        $this->adminDataMock->expects($this->exactly(1))
            ->method('getLocale')
            ->willReturn('fr_FR');

        $this->assertEquals(
            [
                'fr_FR',
                'de_DE',
            ],
            $this->option->getLocales()
        );
    }

    public function testGetVerbosityLevel(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vv');

        $this->assertEquals('-vv', $this->option->getVerbosityLevel());
    }

    public function testGetMaxExecutionTime(): void
    {
        $this->stageConfigMock->method('get')
            ->with(DeployInterface::VAR_SCD_MAX_EXEC_TIME)
            ->willReturn(10);

        $this->assertSame(
            10,
            $this->option->getMaxExecutionTime()
        );
    }

    public function testHasNoParent(): void
    {
        $this->stageConfigMock->method('get')
            ->with(DeployInterface::VAR_SCD_NO_PARENT)
            ->willReturn(true);

        $this->assertTrue($this->option->hasNoParent());
    }
}
