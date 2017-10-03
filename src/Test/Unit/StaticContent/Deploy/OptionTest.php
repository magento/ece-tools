<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Connection;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\Deploy\Option;
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
     * @var Connection|Mock
     */
    private $connectionMock;

    protected function setUp()
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->connectionMock = $this->createMock(Connection::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->option = new Option(
            $this->environmentMock,
            $this->connectionMock,
            $this->magentoVersionMock
        );
    }

    public function testGetThreadCount()
    {
        $this->environmentMock->expects($this->once())
            ->method('getStaticDeployThreadsCount')
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
        $this->environmentMock->expects($this->once())
            ->method('getStaticDeployExcludeThemes')
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
        $this->environmentMock->expects($this->once())
            ->method('getVariable')
            ->with(Environment::VAR_SCD_STRATEGY)
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
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with(
                'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' ' .
                'UNION SELECT interface_locale FROM admin_user'
            )
            ->willReturn([
                ['value' => 'fr_FR'],
                ['value' => 'de_DE'],
            ]);
        $this->environmentMock->expects($this->exactly(2))
            ->method('getAdminLocale')
            ->willReturn('en_US');

        $this->assertEquals(
            [
                'fr_FR',
                'de_DE',
                'en_US'
            ],
            $this->option->getLocales()
        );
    }

    public function testGetLocalesWithExistsAdminLocale()
    {
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with(
                'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' ' .
                'UNION SELECT interface_locale FROM admin_user'
            )
            ->willReturn([
                ['value' => 'fr_FR'],
                ['value' => 'de_DE'],
            ]);
        $this->environmentMock->expects($this->exactly(1))
            ->method('getAdminLocale')
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
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-vv');

        $this->assertEquals('-vv', $this->option->getVerbosityLevel());
    }
}
