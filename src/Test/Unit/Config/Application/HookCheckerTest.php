<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->checker = new HookChecker($this->environmentMock);
    }

    /**
     * @param array $hooks
     * @param bool $expectedResult
     *
     * @dataProvider  isPostDeployEnabledDataProvider
     */
    public function testIsPostDeployHookEnabled(array $hooks, bool $expectedResult): void
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
                ['post_deploy' => 'php ./vendor/bin/ece-tools run'],
                true,
            ],
            [
                ['deploy' => 'php test\nphp test2\n'],
                false,
            ],
        ];
    }
}
