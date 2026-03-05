<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Step\Build\MarshallFiles;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class MarshallFilesTest extends TestCase
{
    /**
     * @var MarshallFiles
     */
    private $step;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->step = new MarshallFiles(
            $this->fileMock,
            $this->directoryListMock,
            $this->magentoVersionMock
        );
    }

    /**
     * Test execute method.
     *
     * @param bool $isExist
     * @param int $deleteDirectory
     * @param int $createDirectory
     * @dataProvider executeDataProvider
     * @return void
     * @throws \ReflectionException
     */
    #[DataProvider('executeDataProvider')]
    public function testExecuteForMagento21(bool $isExist, int $deleteDirectory, int $createDirectory): void
    {
        $enterpriseFolder = 'magento_root/app/enterprise';
        $varCache = 'magento_root/var/cache/';

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
        $this->fileMock->expects($this->exactly($deleteDirectory))
            ->method('deleteDirectory')
            ->with($varCache)
            ->willReturn(true);
        $this->fileMock->expects($this->exactly($createDirectory))
            ->method('createDirectory')
            ->with($enterpriseFolder, 0777)
            ->willReturn(true);
        $matcher = $this->exactly(2);
        $series = [
            ['magento_root/app/etc/di.xml', 'magento_root/app/di.xml'],
            ['magento_root/app/etc/enterprise/di.xml', 'magento_root/app/enterprise/di.xml']
        ];
        $this->fileMock->expects($matcher)
            ->method('copy')
            ->with(
                $this->callback(function ($param) use ($series, $matcher) {
                    // retrieves arguments
                    $arguments = $series[$this->resolveInvocations($matcher) - 1];
                    // performs assertion on the argument
                    $this->assertStringContainsString($arguments[0], $param);
                    return true;
                }),
                $this->callback(function ($param) use ($series, $matcher) {
                    // retrieves arguments
                    $arguments = $series[$this->resolveInvocations($matcher) - 1];
                    // performs assertion on the argument
                    $this->assertStringContainsString($arguments[1], $param);
                    return true;
                }),
            );
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->willReturnMap([
                [$varCache, $isExist],
                [$enterpriseFolder, $isExist],
                ['magento_root/app/etc/enterprise/di.xml', true],
            ]);

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
            [
                'isExist'         => true,
                'deleteDirectory' => 1,
                'createDirectory' => 0,
            ],
            [
                'isExist'         => false,
                'deleteDirectory' => 0,
                'createDirectory' => 1,
            ],
        ];
    }

    /**
     * Test execute for Magento greater than 2.2.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testExecuteFroMagentoGreater22(): void
    {
        $varCache = 'magento_root/var/cache/';

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($varCache)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with($varCache)
            ->willReturn(true);
        $this->fileMock->expects($this->never())
            ->method('copy');

        $this->step->execute();
    }

    /**
     * Resolve invocations.
     *
     * @param InvocationOrder $matcher
     * @return int
     */
    private function resolveInvocations(InvocationOrder $matcher): int
    {
        if (method_exists($matcher, 'numberOfInvocations')) {
            // PHPUnit 10+ (including PHPUnit 12)
            return $matcher->numberOfInvocations();
        }

        if (method_exists($matcher, 'getInvocationCount')) {
            // before PHPUnit 10
            return $matcher->getInvocationCount();
        }

        $this->fail('Cannot count the number of invocations.');
    }
}
