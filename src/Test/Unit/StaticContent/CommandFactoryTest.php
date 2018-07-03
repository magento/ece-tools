<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\StaticContent\CommandFactory;
use Magento\MagentoCloud\StaticContent\OptionInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class CommandFactoryTest extends TestCase
{
    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var CommandFactory
     */
    private $commandFactory;

    /**
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->globalConfig = $this->createMock(GlobalSection::class);

        $this->commandFactory = new CommandFactory(
            $this->magentoVersionMock,
            $this->globalConfig
        );
    }

    /**
     * @param array $optionConfig
     * @param bool $useScdStrategy
     * @param string $expected
     * @dataProvider createDataProvider
     */
    public function testCreate(array $optionConfig, bool $useScdStrategy, string $expected)
    {
        $this->magentoVersionMock
            ->expects($this->exactly(2))
            ->method('satisfies')
            ->willReturn(!$useScdStrategy);

        $this->assertEquals(
            $expected,
            $this->commandFactory->create($this->createOption($optionConfig, (int)$useScdStrategy))
        );
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [
                [
                    'thread_count' => 3,
                    'excluded_themes' => ['theme1', 'theme2'],
                    'strategy' => 'quick',
                    'locales' => ['en_US'],
                    'is_force' => true,
                    'verbosity_level' => '-v',
                ],
                true,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -f -s quick '
                . '-v --jobs 3 --exclude-theme theme1 --exclude-theme theme2 en_US',
            ],
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                ],
                true,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                . '-v --jobs 1 --exclude-theme theme1 en_US de_DE',
            ],
            [
                [
                    'thread_count' => 3,
                    'excluded_themes' => ['theme1', 'theme2'],
                    'strategy' => 'quick',
                    'locales' => ['en_US'],
                    'is_force' => true,
                    'verbosity_level' => '-v',
                ],
                false,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -f -v --jobs 3 '
                . '--exclude-theme theme1 --exclude-theme theme2 en_US',
            ],
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                ],
                false,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -v --jobs 1 '
                . '--exclude-theme theme1 en_US de_DE',
            ],
        ];
    }

    /**
     * @param array $optionConfig
     * @param int $getStrategyTimes
     *
     * @return Mock|OptionInterface
     */
    private function createOption(array $optionConfig, int $getStrategyTimes)
    {
        $optionMock = $this->getMockForAbstractClass(OptionInterface::class);

        if (isset($optionConfig['thread_count'])) {
            $optionMock->expects($this->once())
                ->method('getThreadCount')
                ->willReturn($optionConfig['thread_count']);
        }
        $optionMock->expects($this->once())
            ->method('getExcludedThemes')
            ->willReturn($optionConfig['excluded_themes']);
        $optionMock->expects($this->exactly($getStrategyTimes))
            ->method('getStrategy')
            ->willReturn($optionConfig['strategy']);
        $optionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn($optionConfig['locales']);
        $optionMock->expects($this->once())
            ->method('isForce')
            ->willReturn($optionConfig['is_force']);
        $optionMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn($optionConfig['verbosity_level']);

        return $optionMock;
    }

    /**
     * @param array $optionConfig
     * @param array $matrix
     * @param array $expected
     * @dataProvider matrixDataProvider
     */
    public function testMatrix(array $optionConfig, array $matrix, array $expected)
    {
        $optionMock = $this->getMockForAbstractClass(OptionInterface::class);

        $optionMock->expects($this->once())
            ->method('getExcludedThemes')
            ->willReturn($optionConfig['excluded_themes']);
        $optionMock->expects($this->any())
            ->method('getStrategy')
            ->willReturn($optionConfig['strategy']);
        $optionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn($optionConfig['locales']);
        $optionMock->expects($this->any())
            ->method('isForce')
            ->willReturn($optionConfig['is_force']);
        $optionMock->expects($this->any())
            ->method('getVerbosityLevel')
            ->willReturn($optionConfig['verbosity_level']);

        $this->assertSame(
            $expected,
            $this->commandFactory->matrix($optionMock, $matrix)
        );
    }

    /**
     * @return array
     */
    public function matrixDataProvider(): array
    {
        return [
            [
                [
                    'thread_count' => 3,
                    'excluded_themes' => ['theme1', 'theme2'],
                    'strategy' => 'quick',
                    'locales' => ['en_US'],
                    'is_force' => true,
                    'verbosity_level' => '-v',
                ],
                [],
                [
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -f -s quick '
                    . '-v --exclude-theme theme1 --exclude-theme theme2 en_US',
                ],
            ],
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                ],
                [
                    'Magento/backend' => [
                        'language' => [],
                    ],
                ],
                [
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                    . '-v --exclude-theme theme1 --exclude-theme Magento/backend en_US de_DE',
                ],
            ],
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                ],
                [
                    'Magento/backend' => null,
                ],
                [
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                    . '-v --exclude-theme theme1 --exclude-theme Magento/backend en_US de_DE',
                ],
            ],
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                ],
                [
                    'Magento/backend' => [
                        'language' => ['en_US', 'fr_FR', 'af_ZA'],
                    ],
                ],
                [
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                    . '-v --exclude-theme theme1 --exclude-theme Magento/backend en_US de_DE',
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                    . '-v --theme Magento/backend en_US fr_FR af_ZA',
                ],
            ],
        ];
    }
}
