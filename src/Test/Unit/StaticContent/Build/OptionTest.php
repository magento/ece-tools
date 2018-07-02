<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;
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
     * @var Environment|MockObject
     */
    private $environmentMock;

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
     * @var SharedConfig|MockObject
     */
    private $configResolverMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->threadCountOptimizerMock = $this->createMock(ThreadCountOptimizer::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->configResolverMock = $this->createMock(SharedConfig::class);
        $this->fileMock = $this->createMock(File::class);

        $this->option = new Option(
            $this->environmentMock,
            $this->arrayManagerMock,
            $this->magentoVersionMock,
            $this->threadCountOptimizerMock,
            $this->stageConfigMock,
            $this->configResolverMock,
            $this->fileMock
        );
    }

    public function testGetThreadCount()
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

    /**
     * @return array
     */
    public function excludedThemesDataProvider(): array
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
        $this->configResolverMock->expects($this->once())
            ->method('resolve')
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

    public function testGetLocales21()
    {
        $this->configResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn(__DIR__ . '/_files/app/etc/config.php');
        $flattenConfig = [
            'scopes' => [
                'websites' => [],
                'stores' => [],
            ],
        ];
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->willReturn(['some' => 'config']);
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
