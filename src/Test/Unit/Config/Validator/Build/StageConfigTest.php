<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\Validator\Build\StageConfig;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Schema\Validator as SchemaValidator;

/**
 * @inheritdoc
 */
class StageConfigTest extends TestCase
{
    /**
     * @var StageConfig
     */
    private $validator;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReaderMock;

    /**
     * @var Validator\ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var SchemaValidator|MockObject
     */
    private $schemaValidatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->resultFactoryMock = $this->createMock(Validator\ResultFactory::class);
        $this->schemaValidatorMock = $this->createMock(\Magento\MagentoCloud\Config\Schema\Validator::class);

        $this->validator = new StageConfig(
            $this->environmentReaderMock,
            $this->resultFactoryMock,
            $this->schemaValidatorMock
        );
    }

    public function testValidate(): void
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                StageConfigInterface::SECTION_STAGE => [
                    StageConfigInterface::STAGE_BUILD => [
                        StageConfigInterface::VAR_VERBOSE_COMMANDS => '-v',
                    ],
                    StageConfigInterface::STAGE_DEPLOY => null,
                ],
            ]);
        $this->schemaValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Validator\Result\Success());
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Validator\Result\Success());

        $this->assertInstanceOf(Validator\Result\Success::class, $this->validator->validate());
    }

    public function testValidateWithError(): void
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                StageConfigInterface::SECTION_STAGE => [
                    StageConfigInterface::STAGE_BUILD => [
                        StageConfigInterface::VAR_VERBOSE_COMMANDS => 'error',
                    ],
                ],
            ]);
        $this->schemaValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Validator\Result\Error('Some error'));
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Environment configuration is not valid. Correct the following items in your .magento.env.yaml file:',
                'Some error',
                AppError::BUILD_WRONG_CONFIGURATION_MAGENTO_ENV_YAML
            )
            ->willReturn(new Validator\Result\Error('Some error'));

        $this->assertInstanceOf(Validator\Result\Error::class, $this->validator->validate());
    }

    public function testWithFileSystemException()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('file system error');

        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new FilesystemException('file system error'));

        $this->validator->validate();
    }
}
