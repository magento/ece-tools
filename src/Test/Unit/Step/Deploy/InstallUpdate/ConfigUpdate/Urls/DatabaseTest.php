<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Urls\Database;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class DatabaseTest extends TestCase
{
    /**
     * @var Database
     */
    private $step;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var UrlManager|MockObject
     */
    private $urlManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->connectionMock  = $this->createMock(ConnectionInterface::class);
        $this->loggerMock      = $this->createMock(LoggerInterface::class);
        $this->urlManagerMock  = $this->createMock(UrlManager::class);

        $this->step = new Database(
            $this->environmentMock,
            $this->connectionMock,
            $this->loggerMock,
            $this->urlManagerMock
        );
    }

    /**
     * Test execute method.
     *
     * @param int $connectionAffectingQueryCount
     * @param array $urlManagerGetUrlsWillReturn
     * @dataProvider executeDataProvider
     * @return void
     * @throws StepException
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(
        int $connectionAffectingQueryCount,
        array $urlManagerGetUrlsWillReturn
    ): void {
        $this->loggerMock->expects($this->once())
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Updating secure and unsecure URLs in core_config_data table.',
                    'Host was replaced: [example1.com] => [example2.com]'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
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
        $this->connectionMock->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn($urlManagerGetUrlsWillReturn);
        
        if ($connectionAffectingQueryCount === 0) {
            $this->connectionMock->expects($this->never())
                ->method('affectingQuery');
        } else {
            $this->connectionMock->expects($this->exactly($connectionAffectingQueryCount))
                ->method('affectingQuery')
                // withConsecutive() alternative.
                ->willReturnCallback(fn($param) => match ($param) {
                    [
                        'UPDATE `core_config_data` SET `value` = REPLACE(`value`, ?, ?) WHERE `value` LIKE ?',
                        ['example1.com', 'example2.com', '%example1.com%']
                    ] => 2,
                    'UPDATE `core_config_data` SET `value` = REPLACE(`value`, ?, ?) WHERE `value` LIKE ?',
                    ['example1.com', 'example2.com', '%example1.com%'] => 0,
                });
        }

        $this->step->execute();
    }

    /**
     * Data provider for execute method.
     *
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            'urls not equal' => [
                'connectionAffectingQueryCount' => 2,
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example2.com', '*' => 'https://subsite---example2.com'],
                    'unsecure' => ['' => 'http://example2.com', '*' => 'http://subsite---example2.com'],
                ],
            ],
            'urls equal' => [
                'connectionAffectingQueryCount' => 0,
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example1.com', '*' => 'https://subsite---example1.com'],
                    'unsecure' => ['' => 'http://example1.com', '*' => 'http://subsite---example1.com'],
                ],
            ],
            'urls not exists' => [
                'connectionAffectingQueryCount' => 0,
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => [],
                    'unsecure' => [],
                ],
            ]
        ];
    }
}
