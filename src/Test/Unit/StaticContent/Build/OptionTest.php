<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\ScdStrategyChecker;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\Build\Option;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use Magento\MagentoCloud\Util\ArrayManager;
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
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ArrayManager|Mock
     */
    private $arrayManagerMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var ThreadCountOptimizer|Mock
     */
    private $threadCountOptimizerMock;

    /**
     * @var BuildInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var ScdStrategyChecker|Mock
     */
    private $scdStrategyCheckerMock;

    protected function setUp()
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->threadCountOptimizerMock = $this->createMock(ThreadCountOptimizer::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->scdStrategyCheckerMock = $this->createMock(ScdStrategyChecker::class);

        $this->option = new Option(
            $this->environmentMock,
            $this->scdStrategyCheckerMock,
            $this->arrayManagerMock,
            $this->magentoVersionMock,
            $this->fileListMock,
            $this->threadCountOptimizerMock,
            $this->stageConfigMock
        );
    }

    public function testGetThreadCount()
    {
        $this->stageConfigMock->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [BuildInterface::VAR_SCD_STRATEGY, 'strategyName'],
                [BuildInterface::VAR_SCD_ALLOWED_STRATEGIES, ['strategyName']],
                [BuildInterface::VAR_SCD_THREADS, 3]
            ]);
        $this->scdStrategyCheckerMock->expects($this->once())
            ->method('getStrategy')
            ->willReturn('strategyName');
        $this->threadCountOptimizerMock->expects($this->once())
            ->method('optimize')
            ->with(3, 'strategyName')
            ->willReturn(3);

        $this->assertEquals(3, $this->option->getThreadCount());
    }

    /**
     * @param $themes
     * @param $expected
     * @dataProvider excludedThemesDataProvider
     */
    public function testGetExcludedThemes($themes, $expected)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SCD_EXCLUDE_THEMES)
            ->willReturn($themes);

        $this->assertEquals(
            $expected,
            $this->option->getExcludedThemes()
        );
    }

    public function excludedThemesDataProvider()
    {
        return [
            [
                '',
                [],
            ],
            [
                'theme1, theme2 ,,  theme3 ',
                ['theme1', 'theme2', 'theme3'],
            ],
            [
                'theme3,,theme4,,,,theme5',
                ['theme3', 'theme4', 'theme5'],
            ],
        ];
    }

    /**
     * Test getting the SCD strategy from the strategy checker.
     */
    public function testGetStrategy()
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [BuildInterface::VAR_SCD_STRATEGY],
                [BuildInterface::VAR_SCD_ALLOWED_STRATEGIES]
            )
            ->willReturn(
                'strategy',
                ['strategy']
            );
        $this->scdStrategyCheckerMock->expects($this->once())
            ->method('getStrategy')
            ->willReturn('strategy');

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
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn(__DIR__ . '/_files/app/etc/config.php');
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
        $this->environmentMock->expects($this->once())
            ->method('getAdminLocale')
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

    public function testGetVerbosityLevel()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vv');

        $this->assertEquals('-vv', $this->option->getVerbosityLevel());
    }
}
