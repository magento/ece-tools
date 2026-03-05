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
use PHPUnit\Framework\Attributes\DataProvider;
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
     * Test validate method.
     *
     * @param array $sessionConfig
     * @param string $expectedResultType
     * @param string|null $expectedErrorMessage
     * @dataProvider validateDataProvider
     * @return void
     */
    #[DataProvider('validateDataProvider')]
    public function testValidate(
        array $sessionConfig,
        string $expectedResultType,
        string | null $expectedErrorMessage = null
    ): void {
        $this->sessionConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($sessionConfig);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $expectedResultType,
                $expectedErrorMessage ? ['error' => $expectedErrorMessage] : $this->anything()
            );

        $this->sessionCredentials->validate();
    }

    /**
     * Test validate method with valkey configuration.
     *
     * @param array $sessionConfig
     * @param string $expectedResultType
     * @param string|null $expectedErrorMessage
     * @dataProvider validateDataProviderValkey
     * @return void
     */
    #[DataProvider('validateDataProviderValkey')]
    public function testValidateValkey(
        array $sessionConfig,
        string $expectedResultType,
        string | null $expectedErrorMessage = null
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
     * Data provider for validate method.
     *
     * @return array
     */
    public static function validateDataProvider(): array
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

    /**
     * Data provider for validate method with valkey configuration.
     *
     * @return array
     */
    public static function validateDataProviderValkey(): array
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
                ['save' => 'valkey'],
                ResultInterface::ERROR,
                'Missed valkey options in session configuration'
            ],
            [
                ['save' => 'valkey', 'valkey' => []],
                ResultInterface::ERROR,
                'Missed host option for valkey in session configuration'
            ]
        ];
    }
}
