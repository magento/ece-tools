<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\AdminData;
use Magento\MagentoCloud\Config\EnvironmentDataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class AdminDataTest extends TestCase
{
    /**
     * @var EnvironmentDataInterface|MockObject
     */
    private $environmentDataMock;

    /**
     * @var AdminData
     */
    private $adminData;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->environmentDataMock = $this->getMockForAbstractClass(EnvironmentDataInterface::class);

        $this->adminData = new AdminData($this->environmentDataMock);
    }

    /**
     * @param array $envVariables
     * @param string $expectedValue
     * @param string $methodName
     * @dataProvider methodsDataProvider
     */
    public function testMethods(array $envVariables, string $expectedValue, string $methodName)
    {
        $this->environmentDataMock->expects($this->once())
            ->method('getVariables')
            ->willReturn($envVariables);

        $this->assertEquals($expectedValue, call_user_func([$this->adminData, $methodName]));
    }

    /**
     * @return array
     */
    public function methodsDataProvider(): array
    {
        return [
            [
                ['ADMIN_LOCALE' => 'fr_FR'],
                'fr_FR',
                'getLocale'
            ],
            [
                [],
                'en_US',
                'getLocale'
            ],
            [
                ['ADMIN_USERNAME' => 'admin'],
                'admin',
                'getUsername'
            ],
            [
                ['ADMIN_FIRSTNAME' => 'name'],
                'name',
                'getFirstName'
            ],
            [
                ['ADMIN_LASTNAME' => 'last name'],
                'last name',
                'getLastName'
            ],
            [
                ['ADMIN_EMAIL' => 'email@example.com'],
                'email@example.com',
                'getEmail'
            ],
            [
                ['ADMIN_PASSWORD' => '1234'],
                '1234',
                'getPassword'
            ],
            [
                ['ADMIN_URL' => 'https://admin.com/'],
                'https://admin.com/',
                'getUrl'
            ],
        ];
    }

    public function testGetDefaultCurrency()
    {
        $this->assertEquals('USD', $this->adminData->getDefaultCurrency());
    }
}
