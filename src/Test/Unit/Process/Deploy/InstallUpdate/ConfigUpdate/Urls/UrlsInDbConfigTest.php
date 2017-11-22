<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls\UrlsInDbConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;

/**
 * @inheritdoc
 */
class UrlsInDbConfigTest extends TestCase
{
    /**
     * @var UrlsInDbConfig
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ConnectionInterface|Mock
     */
    private $connectionMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var UrlManager|Mock
     */
    private $urlManagerMock;


    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->urlManagerMock = $this->createMock(UrlManager::class);

        $this->process = new UrlsInDbConfig(
            $this->environmentMock,
            $this->connectionMock,
            $this->loggerMock,
            $this->urlManagerMock
        );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $loggerInfoExpects
     * @param array $urlManagerGetUrlsWillReturn
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $connectionExpectsAffectingQuery
     * @dataProvider executeDataProvider
     */
    public function testExecute($loggerInfoExpects, $urlManagerGetUrlsWillReturn, $connectionExpectsAffectingQuery)
    {
        $this->loggerMock->expects($loggerInfoExpects)
            ->method('info')
            ->withConsecutive(
                ['Updating secure and unsecure URLs in database.core_config_data table.'],
                ['Replace host: [example1.com] => [example2.com]']
            );
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with(
                'SELECT `value`, `path` FROM `core_config_data` WHERE (`path`=? OR `path`= ?) AND `scope_id` = ?',
                ['web/unsecure/base_url', 'web/secure/base_url', 0]
            )
            ->willReturn([
                ['value' => 'https://example1.com', 'path' => 'web/secure/base_url'],
                ['value' => 'http://example1.com', 'path' => 'web/unsecure/base_url'],
            ]);
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn($urlManagerGetUrlsWillReturn);
        $this->connectionMock->expects($connectionExpectsAffectingQuery)
            ->method('affectingQuery')
            ->withConsecutive(
                [
                    'UPDATE `core_config_data` SET `value` = REPLACE(`value`, ?, ?) WHERE `value` LIKE ?',
                    ['example1.com', 'example2.com', '%example1.com%']
                ],
                [
                    'UPDATE `core_config_data` SET `value` = REPLACE(`value`, ?, ?) WHERE `value` LIKE ?',
                    ['example1.com', 'example2.com', '%example1.com%']
                ]
            )
            ->willReturnOnConsecutiveCalls(2, 0);

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'urls not equal' => [
                'loggerInfoExpects' => $this->exactly(2),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example2.com', '*' => 'https://subsite---example2.com'],
                    'unsecure' => ['' => 'http://example2.com', '*' => 'http://subsite---example2.com'],
                ],
                'connectionExpectsAffectingQuery' => $this->exactly(2)
            ],
            'urls equal' => [
                'loggerInfoExpects' => $this->once(),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example1.com', '*' => 'https://subsite---example1.com'],
                    'unsecure' => ['' => 'http://example1.com', '*' => 'http://subsite---example1.com'],
                ],
                'connectionExpectsAffectingQuery' => $this->never()
            ],
            'urls not exists' => [
                'loggerInfoExpects' => $this->once(),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => [],
                    'unsecure' => [],
                ],
                'connectionExpectsAffectingQuery' => $this->never()
            ]
        ];
    }
}
