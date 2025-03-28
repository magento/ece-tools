<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\DeprecatedVariables;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DeprecatedVariablesTest extends TestCase
{
    /**
     * @var DeprecatedVariables
     */
    private $validator;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var array
     */
    private $envBackup;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createPartialMock(Environment::class, ['getVariables']);
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);

        $this->envBackup = $_ENV;

        $this->validator = new DeprecatedVariables(
            $this->environmentMock,
            $this->resultFactoryMock
        );
    }

    /**
     * @param array $variables
     * @param array $env
     * @param string $expectedResultClass
     * @dataProvider executeDataProvider
     */
    public function testValidate(array $variables, array $env, string $expectedResultClass)
    {
        $this->environmentMock->expects($this->once())
            ->method('getVariables')
            ->willReturn($variables);

        $_ENV = $env;

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                [],
                [],
                Success::class,
            ],
            [
                [DeployInterface::VAR_VERBOSE_COMMANDS => '-v'],
                [],
                Success::class,
            ],
            [
                [DeployInterface::VAR_VERBOSE_COMMANDS => Environment::VAL_ENABLED],
                [],
                Error::class,
            ],
        ];
    }

    public function tearDown(): void
    {
        $_ENV = $this->envBackup;
    }
}
