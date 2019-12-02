<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Magento\Shared\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Shared\WriterInterface;
use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ModuleTest extends TestCase
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);
        $this->writerMock = $this->getMockForAbstractClass(WriterInterface::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);

        $this->module = new Module(
            $this->readerMock,
            $this->writerMock,
            $shellFactoryMock
        );
    }

    public function testRefreshWithMissingModuleConfig()
    {
        $this->readerMock->expects($this->exactly(2))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                [],
                [
                    'modules' => [
                        'Magento_Module1' => 1,
                        'Magento_Module2' => 1,
                    ]
                ]
            );
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('module:enable --all');
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->assertEquals(
            [
                'Magento_Module1',
                'Magento_Module2',
            ],
            $this->module->refresh()
        );
    }

    public function testRefreshWithNewModules()
    {
        $this->readerMock->expects($this->exactly(2))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                [
                    'modules' => [
                        'Magento_Module1' => 1,
                        'Magento_Module2' => 0,
                        'Magento_Module3' => 1,
                    ],
                ],
                [
                    'modules' =>[
                        'Magento_Module1' => 1,
                        'Magento_Module2' => 1,
                        'Magento_Module3' => 1,
                        'Magento_Module4' => 1,
                        'Magento_Module5' => 1,
                    ],
                ]
            );
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('module:enable --all');
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with(['modules' => [
                'Magento_Module1' => 1,
                'Magento_Module2' => 0,
                'Magento_Module3' => 1,
            ]]);

        $this->assertEquals(
            [
                'Magento_Module4',
                'Magento_Module5',
            ],
            $this->module->refresh()
        );
    }
}
