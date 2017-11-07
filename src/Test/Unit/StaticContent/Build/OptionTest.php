<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent\Build;

use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\Build\Option;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var BuildConfig|Mock
     */
    private $buildConfigMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var ThreadCountOptimizer|Mock
     */
    private $threadCountOptimizerMock;

    protected function setUp()
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->buildConfigMock = $this->createMock(BuildConfig::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->threadCountOptimizerMock = $this->createMock(ThreadCountOptimizer::class);

        $this->option = new Option(
            $this->environmentMock,
            $this->arrayManagerMock,
            $this->magentoVersionMock,
            $this->directoryListMock,
            $this->buildConfigMock,
            $this->threadCountOptimizerMock
            $this->fileListMock,
            $this->buildConfigMock,
        );
    }

    public function testGetThreadCount()
    {
        $this->buildConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [BuildConfig::OPT_SCD_THREADS],
                [BuildConfig::OPT_SCD_STRATEGY]
            )
            ->willReturnOnConsecutiveCalls(3, 'strategyName');
        $this->threadCountOptimizerMock->expects($this->once())
            ->method('optimize')
            ->with(3, 'strategyName')
            ->willReturn(3);

        $this->assertEquals(3, $this->option->getTreadCount());
    }

    /**
     * @param $themes
     * @param $expected
     * @dataProvider excludedThemesDataProvider
     */
    public function testGetExcludedThemes($themes, $expected)
    {
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildConfig::OPT_SCD_EXCLUDE_THEMES)
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
                []
            ],
            [
                'theme1, theme2 ,,  theme3 ',
                ['theme1', 'theme2', 'theme3']
            ],
            [
                'theme3,,theme4,,,,theme5',
                ['theme3', 'theme4', 'theme5']
            ]
        ];
    }

    public function testGetStrategy()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildConfig::OPT_SCD_STRATEGY)
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
                'en_US'
            ],
            $this->option->getLocales()
        );
    }

    public function testGetVerbosityLevel()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-vv');

        $this->assertEquals('-vv', $this->option->getVerbosityLevel());
    }
}
