<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\RawEnvVariable;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RawEnvVariableTest extends TestCase
{
    /**
     * @var RawEnvVariable
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->validator = new RawEnvVariable($this->resultFactoryMock, $this->environmentMock);
    }

    /**
     * @dataProvider executeDataProvider
     * @param string $scdThreadsValue
     * @param string $expectedResultMethodName
     */
    public function testExecute(string $scdThreadsValue, string $expectedResultMethodName)
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($scdThreadsValue);
        $this->resultFactoryMock->expects($this->once())
            ->method($expectedResultMethodName);

        $this->validator->validate();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                '3',
                ResultInterface::SUCCESS
            ],
            [
                '3123',
                ResultInterface::SUCCESS
            ],
            [
                '-2',
                ResultInterface::ERROR
            ],
            [
                '3a',
                ResultInterface::ERROR
            ],
            [
                'two',
                ResultInterface::ERROR
            ],
        ];
    }
}
