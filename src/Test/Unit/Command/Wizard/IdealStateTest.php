<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\IdealState;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Validator\Deploy\PostDeploy;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnDeploy;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\ValidatorFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class IdealStateTest extends TestCase
{
    /**
     * @var IdealState
     */
    private $command;

    /**
     * @var OutputFormatter|MockObject
     */
    private $outputFormatterMock;

    /**
     * @var ScdOnBuild|MockObject
     */
    private $validatorFactoryMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);
        $this->validatorFactoryMock = $this->createMock(ValidatorFactory::class);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);

        $this->command = new IdealState(
            $this->outputFormatterMock,
            $this->validatorFactoryMock,
            $this->globalConfigMock
        );
    }

    public function testExecute()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $scdOnDeployValidator = $this->createMock(ScdOnDeploy::class);
        $postDeployValidator = $this->createMock(PostDeploy::class);

        $scdOnDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('Some error'));
        $postDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());

        $this->validatorFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [ScdOnDeploy::class, $scdOnDeployValidator],
                [PostDeploy::class, $postDeployValidator],
            ]);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(true);
        $this->outputFormatterMock->expects($this->never())
            ->method('writeItem');
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'The configured state is ideal');

        $this->command->run($inputMock, $outputMock);
    }

    public function testExecuteWithErrors()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $scdOnDeployValidator = $this->createMock(ScdOnDeploy::class);
        $postDeployValidator = $this->createMock(PostDeploy::class);

        $scdOnDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());
        $postDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('Some error'));

        $this->validatorFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [ScdOnDeploy::class, $scdOnDeployValidator],
                [PostDeploy::class, $postDeployValidator],
            ]);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(false);
        $this->outputFormatterMock->expects($this->exactly(3))
            ->method('writeItem')
            ->withConsecutive(
                [$outputMock, 'The SCD is not set for on-demand or the build stage'],
                [$outputMock, 'Post-deploy hook is not configured'],
                [$outputMock, 'Skip HTML minification is disabled']
            );
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, false, 'The configured state is not ideal');

        $this->command->run($inputMock, $outputMock);
    }
}
