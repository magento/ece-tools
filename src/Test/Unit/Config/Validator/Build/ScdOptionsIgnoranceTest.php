<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Build\ScdOptionsIgnorance;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ScdOptionsIgnoranceTest extends TestCase
{
    /**
     * @var ScdOptionsIgnorance
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReader;

    /**
     * @var ScdOnBuild|MockObject
     */
    private $scdOnBuildValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->environmentReader = $this->createMock(EnvironmentReader::class);
        $this->scdOnBuildValidator = $this->createMock(ScdOnBuild::class);

        $this->validator = new ScdOptionsIgnorance(
            $this->resultFactoryMock,
            $this->environmentReader,
            $this->scdOnBuildValidator
        );
    }

    public function testValidateScdOnBuild()
    {
        $this->scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Success::class));
        $this->environmentReader->expects($this->never())
            ->method('read');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateScdNotOnBuild()
    {
        $errorMock = $this->createConfiguredMock(Error::class, [
            'getError' => 'skip reason'
        ]);
        $this->scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn($errorMock);
        $this->environmentReader->expects($this->any())
            ->method('read')
            ->willReturn([
                StageConfigInterface::SECTION_STAGE => [
                    StageConfigInterface::STAGE_BUILD => [
                        StageConfigInterface::VAR_SCD_THREADS => 3,
                        StageConfigInterface::VAR_SCD_STRATEGY => 'quick',
                    ]
                ]
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'When skip reason, static content deployment does not run during the build phase ' .
                'and the following variables are ignored: SCD_STRATEGY, SCD_THREADS'
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
