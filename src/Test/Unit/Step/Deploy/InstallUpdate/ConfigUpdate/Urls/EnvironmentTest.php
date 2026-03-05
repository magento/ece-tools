<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Urls\Environment;
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
class EnvironmentTest extends TestCase
{
    /**
     * @var Environment
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var UrlManager|MockObject
     */
    private $urlManagerMock;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock     = $this->createMock(LoggerInterface::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->readerMock     = $this->createMock(ReaderInterface::class);
        $this->writerMock     = $this->createMock(WriterInterface::class);

        $this->step = new Environment(
            $this->loggerMock,
            $this->urlManagerMock,
            $this->readerMock,
            $this->writerMock
        );
    }

    /**
     * Test execute method.
     *
     * @param int $loggerInfoCount
     * @param array $urlManagerGetUrlsWillReturn
     * @param int $writerWriteCount
     * @dataProvider executeDataProvider
     * @throws StepException
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(
        int $loggerInfoCount,
        array $urlManagerGetUrlsWillReturn,
        int $writerWriteCount
    ): void {
        $this->loggerMock->expects($this->exactly($loggerInfoCount))
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Updating secure and unsecure URLs in app/etc/env.php file',
                    'Host was replaced: [example1.com] => [example2.com]',
                    'Write the updating base URLs configuration in the app/etc/env.php file'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'system' => [
                    'default' => [
                        'web' => [
                            'secure' => ['base_url' => 'https://example1.com/'],
                            'unsecure' => ['base_url' => 'http://example1.com/']
                        ]
                    ]
                ]
            ]);
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn($urlManagerGetUrlsWillReturn);
        
        if ($writerWriteCount === 0) {
            $this->writerMock->expects($this->never())
                ->method('create');
        } else {
            $this->writerMock->expects($this->once())
                ->method('create')
                ->with([
                    'system' => [
                        'default' => [
                            'web' => [
                                'secure' => ['base_url' => 'https://example2.com/'],
                                'unsecure' => ['base_url' => 'http://example2.com/']
                            ]
                        ]
                    ]
                ]);
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
                'loggerInfoCount' => 3,
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example2.com/', '*' => 'https://subsite---example2.com'],
                    'unsecure' => ['' => 'http://example2.com/', '*' => 'http://subsite---example2.com'],
                ],
                'writerWriteCount' => 1,
            ],
            'urls equal' => [
                'loggerInfoCount' => 1,
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example1.com/', '*' => 'https://subsite---example1.com'],
                    'unsecure' => ['' => 'http://example1.com/', '*' => 'http://subsite---example1.com'],
                ],
                'writerWriteCount' => 0,
            ],
            'urls not exists' => [
                'loggerInfoCount' => 1,
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => [],
                    'unsecure' => [],
                ],
                'writerWriteCount' => 0,
            ],
        ];
    }

    /**
     * Test execute with placeholders method.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteWithPlaceholders(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Updating secure and unsecure URLs in app/etc/env.php file'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'system' => [
                    'default' => [
                        'web' => [
                            'secure' => ['base_url' => '{{base_url}}'],
                            'unsecure' => ['base_url' => '{{unsecure_base_url}}'],
                        ],
                    ],
                ],
            ]);
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn([
                'secure' => ['' => 'https://example1.com/', '*' => 'https://subsite---example1.com'],
                'unsecure' => ['' => 'http://example1.com/', '*' => 'http://subsite---example1.com'],
            ]);

        $this->step->execute();
    }
}
