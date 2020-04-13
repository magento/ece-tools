<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Session\Config;
use Magento\MagentoCloud\Config\Validator\Deploy\SessionCredentials;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SessionCredentialsTest extends TestCase
{
    /**
     * @var SessionCredentials
     */
    private $sessionCredentials;

    /**
     * @var Config|MockObject
     */
    private $sessionConfigMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->sessionConfigMock = $this->createMock(Config::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->sessionCredentials = new SessionCredentials(
            $this->resultFactoryMock,
            $this->sessionConfigMock
        );
    }

    /**
     * @param array $sessionConfig
     * @param string $expectedResultType
     * @param string|null $expectedErrorMessage
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        array $sessionConfig,
        string $expectedResultType,
        string $expectedErrorMessage = null
    ): void {
        $this->sessionConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($sessionConfig);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with($expectedResultType, $expectedErrorMessage ? ['error' => $expectedErrorMessage] : $this->anything());

        $this->sessionCredentials->validate();
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            [
                [],
                ResultInterface::SUCCESS
            ],
            [
                ['some' => 'option'],
                ResultInterface::ERROR,
                'Missed required parameter \'save\' in session configuration'
            ],
            [
                ['save' => 'redis'],
                ResultInterface::ERROR,
                'Missed redis options in session configuration'
            ],
            [
                ['save' => 'redis', 'redis' => []],
                ResultInterface::ERROR,
                'Missed host option for redis in session configuration'
            ]
        ];
    }
}
