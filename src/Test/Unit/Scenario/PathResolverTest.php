<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
use Magento\MagentoCloud\Scenario\PathResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class PathResolverTest extends TestCase
{
    /**
     * @var PathResolver
     */
    private $pathResolver;

    /**
     * @var MockObject|File
     */
    private $fileMock;

    /**
     * @var MockObject|SystemList
     */
    private $systemListMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->systemListMock = $this->createMock(SystemList::class);
        $this->systemListMock->expects($this->once())
            ->method('getRoot')
            ->willReturn('/root');
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('/root/magento');

        $this->pathResolver = new PathResolver($this->fileMock, $this->systemListMock);
    }

    /**
     * @throws ValidationException
     */
    public function testResolveFullPath()
    {
        $scenarioPath = 'path/to/scenario';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($scenarioPath)
            ->willReturn(true);

        $this->assertEquals(
            $scenarioPath,
            $this->pathResolver->resolve($scenarioPath)
        );
    }

    /**
     * @throws ValidationException
     */
    public function testResolveRootPath()
    {
        $scenarioPath = 'path/to/scenario';

        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive([$scenarioPath], ['/root/' . $scenarioPath])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->assertEquals(
            '/root/' . $scenarioPath,
            $this->pathResolver->resolve($scenarioPath)
        );
    }

    /**
     * @throws ValidationException
     */
    public function testResolveMagentoRootPath()
    {
        $scenarioPath = 'path/to/scenario';

        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->withConsecutive([$scenarioPath], ['/root/' . $scenarioPath], ['/root/magento/' . $scenarioPath])
            ->willReturnOnConsecutiveCalls(false, false, true);

        $this->assertEquals(
            '/root/magento/' . $scenarioPath,
            $this->pathResolver->resolve($scenarioPath)
        );
    }

    public function testResolveWithException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Scenario path/to/scenario does not exist');

        $scenarioPath = 'path/to/scenario';

        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->withConsecutive([$scenarioPath], ['/root/' . $scenarioPath], ['/root/magento/' . $scenarioPath])
            ->willReturnOnConsecutiveCalls(false, false, false);

        $this->pathResolver->resolve($scenarioPath);
    }
}
