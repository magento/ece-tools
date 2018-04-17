<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Session\Config;
use Magento\MagentoCloud\Config\Validator\Deploy\SessionCredentials;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class SessionCredentialsTest extends TestCase
{
    /**
     * @var Config|Mock
     */
    private $sessionConfigMock;

    /**
     * @var SessionCredentials
     */
    private $sessionCredentials;

    protected function setUp()
    {
        $this->sessionConfigMock = $this->createMock(Config::class);

        $this->sessionCredentials = new SessionCredentials(
            new ResultFactory(),
            $this->sessionConfigMock
        );
    }

    /**
     * @param array $sessionConfig
     * @param string $expectedResultClass
     * @param string|null $expectedErrorMessage
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $sessionConfig, string $expectedResultClass, string $expectedErrorMessage = null)
    {
        $this->sessionConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($sessionConfig);

        $result = $this->sessionCredentials->validate();

        $this->assertInstanceOf($expectedResultClass, $result);
        if ($expectedErrorMessage) {
            $this->assertContains($expectedErrorMessage, $result->getError());
        }
    }

    public function validateDataProvider()
    {
        return [
            [
                [],
                Success::class
            ],
            [
                ['some' => 'option'],
                Error::class,
                'Missed required parameter \'save\' in session configuration'
            ],
            [
                ['save' => 'redis'],
                Error::class,
                'Missed redis options in session configuration'
            ],
            [
                ['save' => 'redis', 'redis' => []],
                Error::class,
                'Missed host option for redis in session configuration'
            ]
        ];
    }
}
