<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\AppropriateVersion;
use Magento\MagentoCloud\Config\Validator\Deploy\Variable\ConfigurationChecker;
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
     * @var ConfigurationChecker|MockObject
     */
    private $configurationCheckerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->magentoVersion = $this->createMock(MagentoVersion::class);
        $this->configurationCheckerMock = $this->createMock(ConfigurationChecker::class);

        $this->validator = new AppropriateVersion(
            $this->resultFactoryMock,
            $this->magentoVersion,
            $this->configurationCheckerMock
        );
    }

    public function testValidateVersionGreaterTwoDotTwo()
    {
        $this->magentoVersion->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);
        $this->magentoVersion->expects($this->once())
            ->method('satisfies')
            ->willReturn(true);
        $this->configurationCheckerMock->expects($this->never())
            ->method('isConfigured');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateVersionTwoDotOneAndVariablesNotConfigured()
    {
        $this->magentoVersion->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
        $this->magentoVersion->expects($this->once())
            ->method('satisfies')
            ->willReturn(false);
        $this->configurationCheckerMock->expects($this->exactly(4))
            ->method('isConfigured')
            ->willReturn(false);

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateVersionTwoDotOneAndAllVariablesAreConfigured()
    {
        $this->magentoVersion->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
        $this->magentoVersion->expects($this->once())
            ->method('satisfies')
            ->willReturn(false);
        $this->configurationCheckerMock->expects($this->exactly(4))
            ->method('isConfigured')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'The current configuration is not compatible with this version of Magento',
                implode(PHP_EOL, [
                    'CRON_CONSUMERS_RUNNER is available for Magento 2.2.0 and later.',
                    'SCD_STRATEGY is available for Magento 2.2.0 and later.',
                    'SCD_MAX_EXECUTION_TIME is available for Magento 2.2.0 and later.',
                    'GENERATED_CODE_SYMLINK is available for Magento 2.1.x.'
                ])
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
