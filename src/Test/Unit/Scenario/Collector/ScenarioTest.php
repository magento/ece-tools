<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario\Collector;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Scenario\Collector\Scenario;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
use Magento\MagentoCloud\Scenario\PathResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * @inheritDoc
 */
class ScenarioTest extends TestCase
{
    /**
     * @var Scenario
     */
    private $scenarioCollector;

    /**
     * @var PathResolver|MockObject
     */
    private $pathResolverMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var XmlEncoder|MockObject
     */
    private $encoderMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->pathResolverMock = $this->createMock(PathResolver::class);
        $this->fileMock = $this->createMock(File::class);
        $this->encoderMock = $this->createMock(XmlEncoder::class);

        $this->scenarioCollector = new Scenario(
            $this->pathResolverMock,
            $this->fileMock,
            $this->encoderMock
        );
    }

    /**
     * @throws \Magento\MagentoCloud\Scenario\Exception\ValidationException
     */
    public function testCollect()
    {
        $scenario = '/path/to/scenario';

        $this->pathResolverMock->expects($this->once())
            ->method('resolve')
            ->with($scenario)
            ->willReturn($scenario);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('XML File content');
        $this->encoderMock->expects($this->once())
            ->method('decode')
            ->with('XML File content')
            ->willReturn(['scenarios']);

        $this->assertEquals(
            ['scenarios'],
            $this->scenarioCollector->collect($scenario)
        );
    }

    public function testCollectWithException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File does not exist');

        $scenario = '/path/to/scenario';

        $this->pathResolverMock->expects($this->once())
            ->method('resolve')
            ->with($scenario)
            ->willReturn($scenario);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException(new FileSystemException('File does not exist'));

        $this->scenarioCollector->collect($scenario);
    }
}
