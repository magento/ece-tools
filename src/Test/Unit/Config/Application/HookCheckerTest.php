<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Application;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Config\Environment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class HookCheckerTest extends TestCase
{
    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var HookChecker
     */
    private $checker;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->checker = new HookChecker($this->environmentMock);
    }

    /**
     * @param array $hooks
     * @param bool $expectedResult
     * @dataProvider  isPostDeployEnabledDataProvider
     */
    public function testIsPostDeployHookEnabled(array $hooks, bool $expectedResult)
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplication')
            ->willReturn(['hooks' => $hooks]);

        $this->assertEquals($expectedResult, $this->checker->isPostDeployHookEnabled());
    }

    /**
     * @return array
     */
    public function isPostDeployEnabledDataProvider(): array
    {
        return [
            [
                ['post_deploy' => 'php ./vendor/bin/ece-tools post-deploy\nphp test\nphp test2\n'],
                true,
            ],
            [
                ['post_deploy' => 'php test\nphp test2\n'],
                false,
            ],
            [
                ['deploy' => 'php test\nphp test2\n'],
                false,
            ],
        ];
    }
}
