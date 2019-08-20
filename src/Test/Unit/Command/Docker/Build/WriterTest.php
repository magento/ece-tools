<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Docker\Build;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Command\Docker\Build\Writer;
use Magento\MagentoCloud\Docker\ComposeInterface;
use Magento\MagentoCloud\Docker\Config\Dist\Generator;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class WriterTest extends TestCase
{
    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var Generator|MockObject
     */
    private $distGeneratorMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->distGeneratorMock = $this->createMock(Generator::class);

        $this->writer = new Writer($this->fileMock, $this->distGeneratorMock);
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     */
    public function testWrite()
    {
        /** @var ComposeInterface|MockObject $composeMock */
        $composeMock = $this->getMockForAbstractClass(ComposeInterface::class);
        /** @var Repository|MockObject $repositoryMock */
        $repositoryMock = $this->getMockForAbstractClass(Repository::class);

        $this->writer->write($composeMock, $repositoryMock);
    }
}
