<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\DeprecatedVariables;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
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
    protected function setUp()
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
            [
                [DeployInterface::VAR_STATIC_CONTENT_THREADS => 3],
                [],
                Error::class,
            ],
            [
                [],
                [DeployInterface::VAR_STATIC_CONTENT_THREADS => 3],
                Error::class,
            ],
            [
                [DeployInterface::VAR_DO_DEPLOY_STATIC_CONTENT => 1],
                [],
                Error::class,
            ],
            [
                [],
                [DeployInterface::VAR_DO_DEPLOY_STATIC_CONTENT => 1],
                Error::class,
            ],
            [
                [DeployInterface::VAR_SCD_EXCLUDE_THEMES => 'theme'],
                [],
                Error::class,
            ],
        ];
    }

    public function tearDown()
    {
        $_ENV = $this->envBackup;
    }
}
