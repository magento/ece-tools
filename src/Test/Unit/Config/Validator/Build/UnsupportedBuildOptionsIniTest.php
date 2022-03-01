<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\UnsupportedBuildOptionsIni;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class UnsupportedBuildOptionsIniTest extends TestCase
{
    /**
     * @var UnsupportedBuildOptionsIni
     */
    private $validator;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new UnsupportedBuildOptionsIni(
            $this->fileMock,
            $this->fileListMock,
            $this->resultFactoryMock
        );
    }

    public function testValidateSuccess(): void
    {
        $path = '/path/to/build_option.ini';

        $this->fileListMock->expects($this->once())
            ->method('getBuildConfig')
            ->willReturn($path);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateError(): void
    {
        $path = '/path/to/build_options.ini';

        $this->fileListMock->expects($this->exactly(2))
            ->method('getBuildConfig')
            ->willReturn($path);
        $this->fileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn('/path/to/magento.env.yaml');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'The build_options.ini file is no longer supported.',
                'Modify your configuration to specify build options in the magento.env.yaml file'
            );

        $this->validator->validate();
    }
}
