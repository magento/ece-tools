<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Build\AppropriateVersion;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class AppropriateVersionTest extends TestCase
{
    /**
     * @var AppropriateVersion
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersion;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->magentoVersion = $this->createMock(MagentoVersion::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);

        $this->validator = new AppropriateVersion(
            $this->resultFactoryMock,
            $this->magentoVersion,
            $this->stageConfigMock
        );
    }

    public function testValidateVersionGreaterTwoDotTwo()
    {
        $this->magentoVersion->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->stageConfigMock->expects($this->never())
            ->method('get');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateVersionLowerTwoDotTwoAndVariablesEmpty()
    {
        $this->magentoVersion->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [StageConfigInterface::VAR_SCD_STRATEGY, null],
                [StageConfigInterface::VAR_SCD_MAX_EXEC_TIME, null],
            ]);

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateVersionLowerTwoDotTwoAndStrategyConfigured()
    {
        $this->magentoVersion->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [StageConfigInterface::VAR_SCD_STRATEGY, 'quick'],
                [StageConfigInterface::VAR_SCD_MAX_EXEC_TIME, null],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'The current configuration is not compatible with this version of Magento',
                'SCD_STRATEGY is available for Magento 2.2.0 and later.'
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    public function testValidateVersionLowerTwoDotTwoAndOptionsConfigured()
    {
        $this->magentoVersion->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [StageConfigInterface::VAR_SCD_STRATEGY, 'quick'],
                [StageConfigInterface::VAR_SCD_MAX_EXEC_TIME, '1000'],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'The current configuration is not compatible with this version of Magento',
                implode(PHP_EOL, [
                    'SCD_STRATEGY is available for Magento 2.2.0 and later.',
                    'SCD_MAX_EXECUTION_TIME is available for Magento 2.2.0 and later.'
                ])
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
