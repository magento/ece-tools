<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\RemovedSplitDb;
use Magento\MagentoCloud\Config\Validator\Deploy\SplitDb;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for RemovedSplitDb validator
 */
class RemovedSplitDbTest extends TestCase
{
    /**
     * @var RemovedSplitDb
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var SplitDb|MockObject
     */
    private $splitDbMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->splitDbMock = $this->createMock(SplitDb::class);

        $this->validator = new RemovedSplitDb(
            $this->resultFactoryMock,
            $this->magentoVersionMock,
            $this->splitDbMock
        );
    }

    public function testValidateSuccess()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.5.0')
            ->willReturn(false);
        $this->splitDbMock->expects($this->never())
            ->method('isConfigured');

        $this->assertInstanceOf(
            Success::class,
            $this->validator->validate()
        );
    }

    public function testValidateError()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.5.0')
            ->willReturn(true);
        $this->splitDbMock->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->assertInstanceOf(
            Error::class,
            $this->validator->validate()
        );
    }

    public function testValidateWitException()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('some error');

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.5.0')
            ->willThrowException(new UndefinedPackageException('some error'));
        $this->splitDbMock->expects($this->never())
            ->method('isConfigured');

        $this->validator->validate();
    }
}
