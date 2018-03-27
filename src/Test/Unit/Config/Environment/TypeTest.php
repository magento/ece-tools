<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Environment;

use Magento\MagentoCloud\Config\Environment\Type;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class TypeTest extends TestCase
{
    use PHPMock;

    /**
     * @param string $currentUser
     * @param string $expectedType
     * @dataProvider getDataProvider
     */
    public function testGet(string $currentUser, string $expectedType)
    {
        $currentUserMock = $this->getFunctionMock('Magento\MagentoCloud\Config\Environment', 'get_current_user');
        $currentUserMock->expects($this->once())
            ->willReturn($currentUser);

        $environmentType = new Type();
        $this->assertEquals($expectedType, $environmentType->get());
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            ['web', Type::INTEGRATION],
            ['web1', Type::PRODUCTION],
            ['web_test', Type::PRODUCTION],
            ['web_st', Type::PRODUCTION],
            ['web_stg', Type::STAGING],
            ['web_stg1', Type::PRODUCTION],
            ['project_id_stg', Type::STAGING],
            ['project_id', Type::PRODUCTION],
        ];
    }
}
