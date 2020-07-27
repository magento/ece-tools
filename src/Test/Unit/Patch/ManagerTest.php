<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Patch;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ManagerTest extends TestCase
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalSectionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->globalSectionMock = $this->createMock(GlobalSection::class);

        $this->manager = new Manager(
            $this->loggerMock,
            $this->shellMock,
            $this->globalSectionMock
        );
    }

    /**
     * Tests patch applying.
     */
    public function testApply(): void
    {
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./vendor/bin/ece-patches apply --no-interaction')
            ->willReturn($processMock);
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Applying patches'],
                ['End of applying patches']
            );

        $this->manager->apply();
    }

    /**
     * Tests with git-based Magento.
     */
    public function testApplyGit(): void
    {
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(true);

        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Git-based installation. Skipping patches applying');

        $this->manager->apply();
    }

    /**
     * @return array[]
     */
    public function applyDataProvider(): array
    {
        return [
            [
                'deploymentFromGit' => false,
                'qualityPatches' => [],
                'expectedCommand' => 'php ./vendor/bin/ece-patches apply --no-interaction'
            ]
        ];
    }

    /**
     * @throws ShellException
     */
    public function testApplyWithException(): void
    {
        $this->expectException(ShellException::class);
        $this->expectExceptionMessage('Some error');

        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./vendor/bin/ece-patches apply --no-interaction')
            ->willThrowException(new ShellException('Some error'));
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Applying patches']
            );
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Some error');

        $this->manager->apply();
    }
}
