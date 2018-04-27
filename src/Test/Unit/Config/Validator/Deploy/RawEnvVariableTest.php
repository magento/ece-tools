<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\RawEnvVariable;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new RawEnvVariable($this->resultFactoryMock);
    }

    /**
     * @dataProvider executeDataProvider
     * @param string $scdThreadsValue
     * @param string $expectedResultType
     */
    public function testExecute(string $scdThreadsValue, string $expectedResultType)
    {
        $_ENV['STATIC_CONTENT_THREADS'] = $scdThreadsValue;

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with($expectedResultType);

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
            [
                '',
                ResultInterface::ERROR
            ]
        ];
    }

    public function tearDown()
    {
        $_ENV = [];
    }
}
