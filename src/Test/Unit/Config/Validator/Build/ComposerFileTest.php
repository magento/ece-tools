<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\ComposerFile;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ComposerFileTest extends TestCase
{
    /**
     * @var ComposerFile
     */
    private $validator;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new ComposerFile(
            $this->fileListMock,
            new File(),
            $this->magentoVersionMock,
            $this->resultFactoryMock
        );
    }

    public function testValidateCorrectComposerJson()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.3')
            ->willReturn(true);
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn(__DIR__ . '/_files/correct_composer_2.3.json');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateWrongComposerJson()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.3')
            ->willReturn(true);
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn(__DIR__ . '/_files/wrong_composer_2.3.json');
        $this->resultFactoryMock->expects($this->once())
            ->method('error');

        $this->validator->validate();
    }

    public function testValidateMagentoLover2dot3()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.3')
            ->willReturn(false);
        $this->fileListMock->expects($this->never())
            ->method('getMagentoComposer');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateComposerFileNotExists()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.3')
            ->willReturn(true);
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn(__DIR__ . '/_files/file_not_exists.json');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Can\'t read composer.json file: Cannot read contents from file'));

        $this->validator->validate();
    }

    public function testValidateCantGetMagentoVersion()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willThrowException(new UndefinedPackageException('some error'));
        $this->fileListMock->expects($this->never())
            ->method('getMagentoComposer');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Can\'t get magento version: some error'));

        $this->validator->validate();
    }
}
