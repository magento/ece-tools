<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\CommandFactory;
use Magento\MagentoCloud\StaticContent\OptionInterface;
use Magento\MagentoCloud\StaticContent\ThemeResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class CommandFactoryTest extends TestCase
{
    /**
     * @var CommandFactory
     */
    private $commandFactory;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalConfigMock;

    /**
     * @var ThemeResolver|MockObject
     */
    private $themeResolverMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);
        $this->themeResolverMock = $this->createMock(ThemeResolver::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->commandFactory = new CommandFactory(
            $this->magentoVersionMock,
            $this->globalConfigMock,
            $this->themeResolverMock,
            $this->loggerMock
        );
    }

    /**
     * Test create method.
     *
     * @param array $optionConfig
     * @param bool $useScdStrategy
     * @param string $expected
     * @dataProvider createDataProvider
     * @return void
     * @throws \ReflectionException
     */
    #[DataProvider('createDataProvider')]
    public function testCreate(array $optionConfig, bool $useScdStrategy, string $expected): void
    {
        $this->magentoVersionMock
            ->expects($this->exactly(3))
            ->method('satisfies')
            ->willReturn($useScdStrategy);
        $arguments = array_chunk($optionConfig['excluded_themes'], 1);
        $results = $optionConfig['resolve_return'];
        $this->themeResolverMock
            ->expects($this->exactly(count($optionConfig['excluded_themes'])))
            ->method('resolve')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($arguments) use ($results) {
                static $callCount = 0;
                $returnValue = $results[$callCount] ?? null;
                $callCount++;
                return $returnValue;
            });
        $this->assertEquals(
            $expected,
            $this->commandFactory->create(
                $this->createOption($optionConfig, (int)$useScdStrategy),
                $optionConfig['excluded_themes']
            )
        );
    }

    /**
     * Data provider for create method.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function createDataProvider(): array
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
                    'no-parent' => false,
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
                    'no-parent' => true,
                ],
                true,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                . '-v --jobs 1 --max-execution-time 1000 --no-parent --exclude-theme theme1 en_US de_DE',
            ],
            [
                [
                    'thread_count' => 1,
                    'excluded_themes' => ['theme1'],
                    'resolve_return' => ['theme1'],
                    'strategy' => 'compact',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'max_execution_time' => 1000,
                    'no-parent' => true,
                ],
                true,
                'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s compact '
                . '-v --jobs 1 --max-execution-time 1000 --exclude-theme theme1 en_US de_DE',
            ],
            [
                [
                    'thread_count' => 3,
                    'excluded_themes' => ['theme1', 'theme2'],
                    'resolve_return' => ['theme1', 'theme2'],
                    'strategy' => 'compact',
                    'locales' => ['en_US'],
                    'is_force' => true,
                    'verbosity_level' => '-v',
                    'no-parent' => true,
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
                    'resolve_pass' => [['Theme1']],
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
     * Create option mock.
     *
     * @param array $optionConfig
     * @param int $getStrategyTimes
     * @return MockObject|OptionInterface
     */
    private function createOption(array $optionConfig, int $getStrategyTimes): MockObject|OptionInterface
    {
        $optionMock = $this->createMock(OptionInterface::class);

        if (isset($optionConfig['thread_count'])) {
            $optionMock->expects($this->once())
                ->method('getThreadCount')
                ->willReturn($optionConfig['thread_count']);
        }
        $optionMock->expects($this->atLeast($getStrategyTimes))
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
        $optionMock->expects($this->exactly($getStrategyTimes))
            ->method('hasNoParent')
            ->willReturn($optionConfig['no-parent'] ?? false);

        return $optionMock;
    }

    /**
     * Test matrix method.
     *
     * @param array $optionConfig
     * @param array $matrix
     * @param array $expected
     * @dataProvider matrixDataProvider
     * @return void
     * @throws \ReflectionException
     */
    #[DataProvider('matrixDataProvider')]
    public function testMatrix(array $optionConfig, array $matrix, array $expected): void
    {
        /** @var OptionInterface|MockObject $optionMock */
        $optionMock = $this->createMock(OptionInterface::class);
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
        $arguments = $optionConfig['resolve_pass'];
        $results = $optionConfig['resolve_return'];
        $this->themeResolverMock
            ->expects($this->exactly(count($optionConfig['resolve_pass'])))
            ->method('resolve')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($arguments) use ($results) {
                static $callCount = 0;
                $returnValue = $results[$callCount] ?? null;
                $callCount++;
                return $returnValue;
            });
        $this->assertSame(
            $expected,
            $this->commandFactory->matrix($optionMock, $matrix)
        );
    }

    /**
     * Data provider for matrix method.
     *
     * @return array
     */
    public static function matrixDataProvider(): array
    {
        return [
            [
                [
                    'thread_count' => 3,
                    'strategy' => 'quick',
                    'locales' => ['en_US'],
                    'is_force' => true,
                    'verbosity_level' => '-v',
                    'resolve_return' => [],
                    'resolve_pass' => [],
                ],
                [],
                [
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -f -s quick '
                    . '-v en_US',
                ],
            ],
            [
                [
                    'thread_count' => 1,
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'resolve_return' => ['Magento/backend'],
                    'resolve_pass' => [['Magento/backend']],
                ],
                [
                    'Magento/backend' => [
                        'language' => [],
                    ],
                ],
                [
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                    . '-v --exclude-theme Magento/backend en_US de_DE',
                ],
            ],
            [
                [
                    'thread_count' => 1,
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'resolve_return' => ['Magento/backend'],
                    'resolve_pass' => [['Magento/backend']],
                ],
                [
                    'Magento/backend' => null,
                ],
                [
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                    . '-v --exclude-theme Magento/backend en_US de_DE',
                ],
            ],
            [
                [
                    'thread_count' => 1,
                    'strategy' => 'quick',
                    'locales' => ['en_US', 'de_DE'],
                    'is_force' => false,
                    'verbosity_level' => '-v',
                    'resolve_return' => ['Magento/backend', 'Magento/backend'],
                    'resolve_pass' => [['Magento/backend'], ['Magento/backend']],
                ],
                [
                    'Magento/backend' => [
                        'language' => ['en_US', 'fr_FR', 'af_ZA'],
                    ],
                ],
                [
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                    . '-v --exclude-theme Magento/backend en_US de_DE',
                    'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
                    . '-v --theme Magento/backend en_US fr_FR af_ZA',
                ],
            ],
        ];
    }

    /**
     * Test create no resolve.
     *
     * @return void
     */
    public function testCreateNoResolve(): void
    {
        $excludedThemes = ['Theme1'];
        $optionConfig = [
            'thread_count' => 1,
            'resolve_pass' => [['Theme1']],
            'resolve_return' => [''],
            'strategy' => 'quick',
            'locales' => ['en_US', 'de_DE'],
            'is_force' => false,
            'verbosity_level' => '-v',
        ];
        $useScdStrategy = false;
        $expected = 'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -v --jobs 1 en_US de_DE';

        $this->magentoVersionMock
            ->expects($this->exactly(3))
            ->method('satisfies')
            ->willReturn($useScdStrategy);
        $arguments = array_chunk($excludedThemes, 1);
        $results = $optionConfig['resolve_return'];
        $this->themeResolverMock
            ->expects($this->exactly($this->count($excludedThemes)))
            ->method('resolve')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($arguments) use ($results) {
                static $callCount = 0;
                $returnValue = $results[$callCount] ?? null;
                $callCount++;
                return $returnValue;
            });

        $this->assertEquals(
            $expected,
            $this->commandFactory->create($this->createOption($optionConfig, (int)$useScdStrategy), $excludedThemes)
        );
    }

    /**
     * Test matrix no resolve.
     *
     * @return void
     */
    public function testMatrixNoResolve(): void
    {
        $matrix = [
            'Magento/Backend' => [
                'language' => ['en_US', 'fr_FR', 'af_ZA'],
            ],
        ];
        $expected = [
            'php ./bin/magento setup:static-content:deploy --ansi --no-interaction -s quick '
            . '-v --no-html-minify en_US de_DE'
        ];

        /** @var OptionInterface|MockObject $optionMock */
        $optionMock = $this->createMock(OptionInterface::class);

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
            ->expects($this->exactly(3))
            ->method('satisfies')
            ->willReturn(true);
        $this->themeResolverMock
            ->expects($this->exactly(2))
            ->method('resolve')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['Magento/Backend'] => '',
                ['Magento/Backend'] => ''
            });
        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with('Unable to resolve Magento/Backend to an available theme.');
        $this->globalConfigMock->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(true);

        $this->assertSame(
            $expected,
            $this->commandFactory->matrix($optionMock, $matrix)
        );
    }
}
