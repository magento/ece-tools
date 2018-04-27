<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\DeprecatedBuildOptionsIni;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class DeprecatedBuildOptionsIniTest extends TestCase
{
    /**
     * @var DeprecatedBuildOptionsIni
     */
    private $validator;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new DeprecatedBuildOptionsIni(
            $this->fileMock,
            $this->fileListMock,
            $this->resultFactoryMock
        );
    }

    public function testValidateSuccess()
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
            ->method('create')
            ->with(ResultInterface::SUCCESS);

        $this->validator->validate();
    }

    public function testValidateError()
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
            ->method('create')
            ->with(ResultInterface::ERROR, [
                'error' => 'File build_options.ini is deprecated',
                'suggestion' => 'Please use new configuration file magento.env.yaml'
            ]);

        $this->validator->validate();
    }
}
