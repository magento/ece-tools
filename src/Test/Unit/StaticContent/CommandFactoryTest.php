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
use Magento\MagentoCloud\StaticContent\ThemeResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

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
     * @var ThemeResolver|Mock
     */
    private $themeResolverMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->globalConfig = $this->createMock(GlobalSection::class);
        $this->themeResolverMock = $this->createMock(ThemeResolver::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->commandFactory = new CommandFactory(
            $this->magentoVersionMock,
            $this->globalConfig,
            $this->themeResolverMock,
            $this->loggerMock
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
            ->willReturn($useScdStrategy);
        $this->themeResolverMock
            ->expects($this->exactly(count($optionConfig['excluded_themes'])))
            ->method('resolve')
            ->withConsecutive(...array_chunk($optionConfig['excluded_themes'], 1))
            ->willReturnOnConsecutiveCalls(...$optionConfig['resolve_return']);

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
                    'resolve_return' => ['theme1', 'theme2'],
                    'strategy' => 'quick',
                    'locales' => ['en_US'],
                    'is_force' => true,
                    'verbosity_level' => '-v',
                    'max_execution_time' => null,
                ],
                true,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -f -s quick '
                . '-v --jobs 3 --exclude-theme theme1 --exclude-theme theme2 en_US',
            ],
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['theme1'],
                    'resolve_return' => ['theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'max_execution_time' => 1000,
                ],
                true,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                . '-v --jobs 1 --max-execution-time 1000 --exclude-theme theme1 en_US de_DE',
            ],
            [
                [
                    'thread_count' => 3,
                    'excluded_themes' => ['theme1', 'theme2'],
                    'resolve_return' => ['theme1', 'theme2'],
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
                    'resolve_return' => ['theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'max_execution_time' => 1000,
                ],
                false,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -v --jobs 1 '
                . '--exclude-theme theme1 en_US de_DE',
            ],
            [
                [
                    'thread_count' => 3,
                    'excluded_themes' => ['Theme1', 'Theme2'],
                    'resolve_return' => ['theme1', 'theme2'],
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
                    'excluded_themes' => ['Theme1'],
                    'resolve_pass' =>  [['Theme1']],
                    'resolve_return' => ['theme1'],
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
        $optionMock->expects($this->exactly($getStrategyTimes))
            ->method('getMaxExecutionTime')
            ->willReturn($optionConfig['max_execution_time'] ?? null);

        return $optionMock;
    }

    /**
     * @param array $optionConfig
     * @param array $matrix
     * @param array $expected
     * @dataProvider matrixDataProvider
     * @dataProvider matrixResolveDataProvider
     */
    public function testMatrix(array $optionConfig, array $matrix, array $expected)
    {
        /** @var OptionInterface|MockObject $optionMock */
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
        $this->magentoVersionMock
            ->expects($this->any())
            ->method('satisfies')
            ->willReturn(true);
        $this->themeResolverMock
            ->expects($this->exactly(count($optionConfig['resolve_pass'])))
            ->method('resolve')
            ->withConsecutive(...$optionConfig['resolve_pass'])
            ->willReturnOnConsecutiveCalls(...$optionConfig['resolve_return']);

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
                    'resolve_return' => ['theme1', 'theme2'],
                    'resolve_pass' => [['theme1'], ['theme2']],
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
                    'resolve_return' => ['theme1', 'Magento/backend'],
                    'resolve_pass' => [['theme1'], ['Magento/backend']],
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
                    'resolve_return' => ['theme1', 'Magento/backend'],
                    'resolve_pass' => [['theme1'], ['Magento/backend']],
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
                    'resolve_return' => ['theme1', 'Magento/backend', 'Magento/backend'],
                    'resolve_pass' => [['theme1'], ['Magento/backend'], ['Magento/backend']],
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

    public function matrixResolveDataProvider()
    {
        return [
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['Theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'resolve_return' => ['theme1', 'Magento/backend', 'Magento/backend'],
                    'resolve_pass' => [['Theme1'], ['Magento/backend'], ['Magento/backend']],
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
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'resolve_return' => ['theme1', 'Magento/backend', 'Magento/backend'],
                    'resolve_pass' => [['theme1'], ['Magento/Backend'], ['Magento/Backend']],
                ],
                [
                    'Magento/Backend' => [
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
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['Theme1'],
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'resolve_return' => ['theme1', 'Magento/backend', 'Magento/backend'],
                    'resolve_pass' => [['Theme1'], ['Magento/Backend'], ['Magento/Backend']],
                ],
                [
                    'Magento/Backend' => [
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

    public function testCreateNoResolve()
    {
        $optionConfig = [
            'thread_count' => 1,
            'excluded_themes' => ['Theme1'],
            'resolve_pass' =>  [['Theme1']],
            'resolve_return' => [''],
            'strategy' => 'quick',
            'locales' => ['en_US', 'de_DE'],
            'is_force' => false,
            'verbosity_level' => '-v',
        ];
        $useScdStrategy = false;
        $expected = 'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -v --jobs 1 en_US de_DE';

        $this->magentoVersionMock
            ->expects($this->exactly(2))
            ->method('satisfies')
            ->willReturn($useScdStrategy);
        $this->themeResolverMock
            ->expects($this->exactly(count($optionConfig['excluded_themes'])))
            ->method('resolve')
            ->withConsecutive(...array_chunk($optionConfig['excluded_themes'], 1))
            ->willReturnOnConsecutiveCalls(...$optionConfig['resolve_return']);

        $this->assertEquals(
            $expected,
            $this->commandFactory->create($this->createOption($optionConfig, (int)$useScdStrategy))
        );
    }

    public function testMatrixNoResolve()
    {
        $matrix = [
            'Magento/Backend' => [
                'language' => ['en_US', 'fr_FR', 'af_ZA'],
            ],
        ];
        $expected =[ 'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
            . '-v --exclude-theme theme1 en_US de_DE' ];

        /** @var OptionInterface|MockObject $optionMock */
        $optionMock = $this->getMockForAbstractClass(OptionInterface::class);

        $optionMock->expects($this->once())
            ->method('getExcludedThemes')
            ->willReturn(['theme1']);
        $optionMock->expects($this->any())
            ->method('getStrategy')
            ->willReturn('quick');
        $optionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn(['en_US', 'de_DE']);
        $optionMock->expects($this->any())
            ->method('isForce')
            ->willReturn(false);
        $optionMock->expects($this->any())
            ->method('getVerbosityLevel')
            ->willReturn('-v');
        $this->magentoVersionMock
            ->expects($this->exactly(2))
            ->method('satisfies')
            ->willReturn(true);
        $this->themeResolverMock
            ->expects($this->exactly(3))
            ->method('resolve')
            ->withConsecutive(
                ['theme1'],
                ['Magento/Backend'],
                ['Magento/Backend']
            )
            ->willReturnOnConsecutiveCalls(
                'theme1',
                '',
                ''
            );
        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with('Unable to resolve Magento/Backend to an available theme.');

        $this->assertSame(
            $expected,
            $this->commandFactory->matrix($optionMock, $matrix)
        );
    }
}
