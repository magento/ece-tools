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
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var AdminDataInterface|Mock
     */
    private $adminDataMock;

    /**
     * @var Connection|Mock
     */
    private $connectionMock;

    /**
     * @var ThreadCountOptimizer|Mock
     */
    private $threadCountOptimizerMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
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

    public function testGetThreadCount()
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
    public function testGetStrategy()
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

    public function testIsForce()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $this->assertTrue($this->option->isForce());
    }

    public function testGetLocales()
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

    public function testGetLocalesWithExistsAdminLocale()
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

    public function testGetVerbosityLevel()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vv');

        $this->assertEquals('-vv', $this->option->getVerbosityLevel());
    }
}
