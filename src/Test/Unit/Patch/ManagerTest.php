<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Patch;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @see Manager
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
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->manager = new Manager(
            $this->loggerMock,
            $this->shellMock,
            $this->magentoVersionMock
        );
    }

    /**
     * @throws ConfigException
     */
    public function testApply(): void
    {
        $this->magentoVersionMock->expects(self::once())
            ->method('isGitInstallation')
            ->willReturn(false);

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->shellMock->expects(self::once())
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
     * @throws ConfigException
     */
    public function testApplyGit(): void
    {
        $this->magentoVersionMock->expects(self::once())
            ->method('isGitInstallation')
            ->willReturn(true);

        $this->shellMock->expects(self::never())
            ->method('execute');
        $this->loggerMock->expects(self::once())
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
     * @throws ConfigException
     */
    public function testApplyWithException(): void
    {
        $this->expectException(ShellException::class);
        $this->expectExceptionMessage('Some error');

        $this->magentoVersionMock->expects(self::once())
            ->method('isGitInstallation')
            ->willReturn(false);
        $this->shellMock->expects(self::once())
            ->method('execute')
            ->with('php ./vendor/bin/ece-patches apply --no-interaction')
            ->willThrowException(new ShellException('Some error'));
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Applying patches']
            );
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Some error');

        $this->manager->apply();
    }
}
