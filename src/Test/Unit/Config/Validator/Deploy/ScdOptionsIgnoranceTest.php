<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\ScdOptionsIgnorance;
use Magento\MagentoCloud\Config\Validator\Deploy\Variable\ConfigurationChecker;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnDeploy;
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
     * @var ScdOnDeploy|MockObject
     */
    private $scdOnDeployValidator;

    /**
     * @var ConfigurationChecker|MockObject
     */
    private $configurationCheckerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->scdOnDeployValidator = $this->createMock(ScdOnDeploy::class);
        $this->configurationCheckerMock = $this->createMock(ConfigurationChecker::class);

        $this->validator = new ScdOptionsIgnorance(
            $this->resultFactoryMock,
            $this->scdOnDeployValidator,
            $this->configurationCheckerMock
        );
    }

    public function testValidateScdOnDeploy()
    {
        $this->scdOnDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Success::class));
        $this->configurationCheckerMock->expects($this->never())
            ->method('isConfigured');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateScdNotOnDeploy()
    {
        $errorMock = $this->createConfiguredMock(Error::class, [
            'getError' => 'skip reason'
        ]);
        $this->scdOnDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn($errorMock);
        $this->configurationCheckerMock->expects($this->exactly(2))
            ->method('isConfigured')
            ->willReturnMap([
                [StageConfigInterface::VAR_SCD_STRATEGY, false, true],
                [StageConfigInterface::VAR_SCD_THREADS, false, false],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'When skip reason, static content deployment does not run during the deploy phase ' .
                'and the following variables are ignored: SCD_STRATEGY'
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
