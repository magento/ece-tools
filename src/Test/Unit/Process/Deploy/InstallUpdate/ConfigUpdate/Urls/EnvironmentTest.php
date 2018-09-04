<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls\Environment;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;

/**
 * @inheritdoc
 */
class EnvironmentTest extends TestCase
{
    /**
     * @var Environment
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var UrlManager|Mock
     */
    private $urlManagerMock;

    /**
     * @var Reader|Mock
     */
    private $readerMock;

    /**
     * @var Writer|Mock
     */
    private $writerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->readerMock = $this->createMock(Reader::class);
        $this->writerMock = $this->createMock(Writer::class);

        $this->process = new Environment(
            $this->loggerMock,
            $this->urlManagerMock,
            $this->readerMock,
            $this->writerMock
        );
    }

    /**
     * @param InvokedCount $loggerInfoExpects
     * @param array $urlManagerGetUrlsWillReturn
     * @param InvokedCount $writerWriteExpects
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        InvokedCount $loggerInfoExpects,
        array $urlManagerGetUrlsWillReturn,
        InvokedCount $writerWriteExpects
    ) {
        $this->loggerMock->expects($loggerInfoExpects)
            ->method('info')
            ->withConsecutive(
                ['Updating secure and unsecure URLs in app/etc/env.php file'],
                ['Host was replaced: [example1.com] => [example2.com]'],
                ['Write the updating base URLs configuration in the app/etc/env.php file']
            );
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
        $this->writerMock->expects($writerWriteExpects)
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

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            'urls not equal' => [
                'loggerInfoExpects' => $this->exactly(3),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example2.com/', '*' => 'https://subsite---example2.com'],
                    'unsecure' => ['' => 'http://example2.com/', '*' => 'http://subsite---example2.com'],
                ],
                'writerWriteExpects' => $this->once()
            ],
            'urls equal' => [
                'loggerInfoExpects' => $this->once(),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example1.com/', '*' => 'https://subsite---example1.com'],
                    'unsecure' => ['' => 'http://example1.com/', '*' => 'http://subsite---example1.com'],
                ],
                'writerWriteExpects' => $this->never()
            ],
            'urls not exists' => [
                'loggerInfoExpects' => $this->once(),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => [],
                    'unsecure' => [],
                ],
                'writerWriteExpects' => $this->never()
            ],
        ];
    }

    public function testExecuteWithPlaceholders()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->withConsecutive(
                ['Updating secure and unsecure URLs in app/etc/env.php file']
            );
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

        $this->process->execute();
    }
}
