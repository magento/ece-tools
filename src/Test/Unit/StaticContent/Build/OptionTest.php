<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\StaticContent\Build;

use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\Magento\Shared\Resolver;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\Build\Option;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use Magento\MagentoCloud\Util\ArrayManager;
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
     * @var ArrayManager|MockObject
     */
    private $arrayManagerMock;

    /**
     * @var ThreadCountOptimizer|MockObject
     */
    private $threadCountOptimizerMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var Resolver|MockObject
     */
    private $configResolverMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->adminDataMock = $this->getMockForAbstractClass(AdminDataInterface::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->threadCountOptimizerMock = $this->createMock(ThreadCountOptimizer::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->configResolverMock = $this->createMock(Resolver::class);

        $this->option = new Option(
            $this->adminDataMock,
            $this->arrayManagerMock,
            $this->magentoVersionMock,
            $this->threadCountOptimizerMock,
            $this->stageConfigMock,
            $this->configResolverMock
        );
    }

    public function testGetThreadCount(): void
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [BuildInterface::VAR_SCD_STRATEGY, 'strategyName'],
                [BuildInterface::VAR_SCD_THREADS, 3],
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
                [BuildInterface::VAR_SCD_STRATEGY]
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
        $this->configResolverMock->expects($this->once())
            ->method('read')
            ->willReturn(['some' => 'config']);
        $flattenConfig = [
            'scopes' => [
                'websites' => [],
                'stores' => [],
            ],
        ];
        $this->arrayManagerMock->expects($this->once())
            ->method('flatten')
            ->willReturn([
                'scopes' => [
                    'websites' => [],
                    'stores' => [],
                ],
            ]);
        $this->arrayManagerMock->expects($this->exactly(2))
            ->method('filter')
            ->willReturnMap([
                [$flattenConfig, 'general/locale/code', true, ['fr_FR']],
                [$flattenConfig, 'admin_user/locale/code', false, ['es_ES']],
            ]);
        $this->adminDataMock->expects($this->once())
            ->method('getLocale')
            ->willReturn('ua_UA');

        $this->assertEquals(
            [
                'ua_UA',
                'fr_FR',
                'es_ES',
                'en_US',
            ],
            $this->option->getLocales()
        );
    }

    public function testGetLocales21(): void
    {
        $this->configResolverMock->expects($this->once())
            ->method('read')
            ->willReturn(['some' => 'config']);
        $flattenConfig = [
            'scopes' => [
                'websites' => [],
                'stores' => [],
            ],
        ];
        $this->arrayManagerMock->expects($this->once())
            ->method('flatten')
            ->with(['some' => 'config'])
            ->willReturn([
                'scopes' => [
                    'websites' => [],
                    'stores' => [],
                ],
            ]);
        $this->arrayManagerMock->expects($this->exactly(2))
            ->method('filter')
            ->willReturnMap([
                [$flattenConfig, 'general/locale/code', true, ['fr_FR']],
                [$flattenConfig, 'admin_user/locale/code', false, ['es_ES']],
            ]);
        $this->adminDataMock->expects($this->once())
            ->method('getLocale')
            ->willReturn('ua_UA');

        $this->assertEquals(
            [
                'ua_UA',
                'fr_FR',
                'es_ES',
                'en_US',
            ],
            $this->option->getLocales()
        );
    }

    public function testGetVerbosityLevel(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vv');

        $this->assertEquals('-vv', $this->option->getVerbosityLevel());
    }

    public function testGetMaxExecutionTime(): void
    {
        $this->stageConfigMock->method('get')
            ->with(BuildInterface::VAR_SCD_MAX_EXEC_TIME)
            ->willReturn(10);

        $this->assertSame(
            10,
            $this->option->getMaxExecutionTime()
        );
    }

    public function testHasNoParent(): void
    {
        $this->stageConfigMock->method('get')
            ->with(BuildInterface::VAR_SCD_NO_PARENT)
            ->willReturn(true);

        $this->assertTrue($this->option->hasNoParent());
    }
}
