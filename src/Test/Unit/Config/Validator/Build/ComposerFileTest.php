<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Validator\Build\ComposerFile;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
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
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->managerMock = $this->createMock(Manager::class);

        $this->validator = new ComposerFile(
            $this->fileListMock,
            new File(),
            $this->magentoVersionMock,
            $this->resultFactoryMock,
            $this->managerMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testValidateCorrectComposerJson(): void
    {
        $series = [
            [['2.3'], true],
            [['2.4.3'], false],
        ];
        $this->magentoVersionMock->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn(__DIR__ . '/_files/correct_composer_2.3.json');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @inheritdoc
     */
    public function testValidateCorrectLaminasComposerJson(): void
    {
        $series = [
            [['2.3'], true],
            [['2.4.3'], false],
        ];
        $this->magentoVersionMock->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn(__DIR__ . '/_files/correct_composer_2.3_2.json');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @inheritdoc
     */
    public function testValidateCorrectAutoload243ComposerJson(): void
    {
        $series = [
            [['2.3'], true],
            [['2.4.3'], false],
        ];
        $this->magentoVersionMock->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn(__DIR__ . '/_files/correct_composer_2.3_2.json');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @inheritdoc
     */
    public function testValidateWrongComposerJson(): void
    {
        $series = [
            [['2.3'], true],
            [['2.4.3'], false],
        ];
        $this->magentoVersionMock->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn(__DIR__ . '/_files/wrong_composer_2.3.json');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Required configuration is missed in autoload section of composer.json file.',
                'Add ("Laminas\Mvc\Controller\: "setup/src/Zend/Mvc/Controller/") to autoload -> psr-4 ' .
                'section and re-run "composer update" command locally. Then commit new composer.json ' .
                'and composer.lock files.',
                Error::BUILD_COMPOSER_MISSED_REQUIRED_AUTOLOAD
            );
        $this->managerMock->expects($this->once())
            ->method('has')
            ->with('laminas/laminas-mvc')
            ->willReturn(true);

        $this->validator->validate();
    }

    /**
     * @inheritdoc
     */
    public function testValidateMagentoLower23(): void
    {
        $series = [
            [['2.3'], false],
        ];
        $this->magentoVersionMock->expects($this->exactly(1))
            ->method('isGreaterOrEqual')
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileListMock->expects($this->never())
            ->method('getMagentoComposer');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @inheritdoc
     */
    public function testValidateMagentoHigherEqual243(): void
    {
        $series = [
            [['2.3'], true],
            [['2.4.3'], true],
        ];
        $this->magentoVersionMock->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileListMock->expects($this->never())
            ->method('getMagentoComposer');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @inheritdoc
     */
    public function testValidateComposerFileNotExists(): void
    {
        $series = [
            [['2.3'], true],
            [['2.4.3'], false],
        ];
        $this->magentoVersionMock->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn(__DIR__ . '/_files/file_not_exists.json');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                $this->stringStartsWith('Can\'t read composer.json file: Cannot read contents from file'),
                '',
                Error::BUILD_CANT_READ_COMPOSER_JSON
            );

        $this->validator->validate();
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testValidateCantGetMagentoVersion(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willThrowException(new UndefinedPackageException('some error'));
        $this->fileListMock->expects($this->never())
            ->method('getMagentoComposer');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                $this->stringStartsWith('Can\'t get magento version: some error'),
                '',
                Error::BUILD_COMPOSER_PACKAGE_NOT_FOUND
            );

        $this->validator->validate();
    }
}
