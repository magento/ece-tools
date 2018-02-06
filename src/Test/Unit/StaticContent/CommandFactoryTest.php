<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\StaticContent\CommandFactory;
use Magento\MagentoCloud\StaticContent\OptionInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class CommandFactoryTest extends TestCase
{
    /**
     * @var CommandFactory
     */
    private $commandFactory;

    public function setUp()
    {
        $this->commandFactory = new CommandFactory();
    }

    /**
     * @param array $optionConfig
     * @param $expected
     * @dataProvider createDataProvider
     */
    public function testCreate(array $optionConfig, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->commandFactory->create($this->createOption($optionConfig))
        );
    }

    /**
     * @return array
     */
    public function createDataProvider()
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
                'php ./bin/magento setup:static-content:deploy --exclude-theme=theme1 --exclude-theme=theme2 -s ' .
                'quick -v en_US --jobs=3',
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
                'php ./bin/magento setup:static-content:deploy --exclude-theme=theme1 -s quick -v en_US de_DE --jobs=1',
            ],
        ];
    }

    /**
     * @param array $optionConfig
     * @return Mock|OptionInterface
     */
    private function createOption(array $optionConfig)
    {
        $optionMock = $this->getMockBuilder(OptionInterface::class)
            ->getMockForAbstractClass();

        if (isset($optionConfig['thread_count'])) {
            $optionMock->expects($this->once())
                ->method('getThreadCount')
                ->willReturn($optionConfig['thread_count']);
        }
        $optionMock->expects($this->once())
            ->method('getExcludedThemes')
            ->willReturn($optionConfig['excluded_themes']);
        $optionMock->expects($this->once())
            ->method('getStrategy')
            ->willReturn($optionConfig['strategy']);
        $optionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn($optionConfig['locales']);
        $optionMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn($optionConfig['verbosity_level']);

        return $optionMock;
    }
}
