<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Package;

use Composer\Package\PackageInterface;
use Composer\Semver\Comparator;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Package\PhpRedisSessionAbstractVersion;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class PhpRedisSessionAbstractVersionTest extends TestCase
{
    /**
     * @var PhpRedisSessionAbstractVersion
     */
    private $redisSessionVersion;

    /**
     * @var Manager|Mock
     */
    private $managerMock;

    /**
     * @var PackageInterface|Mock
     */
    private $packageMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->managerMock = $this->createMock(Manager::class);
        $this->packageMock = $this->getMockForAbstractClass(PackageInterface::class);

        $this->redisSessionVersion = new PhpRedisSessionAbstractVersion(
            $this->managerMock,
            new Comparator()
        );
    }

    /**
     * @param string $version
     * @param string $packageVersion
     * @param bool $expected
     * @dataProvider isGreaterThanDataProvider
     */
    public function testIsGreaterThan(string $version, string $packageVersion, bool $expected)
    {
        $this->managerMock->method('get')
            ->with('colinmollenhour/php-redis-session-abstract')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($packageVersion);

        $this->assertSame(
            $expected,
            $this->redisSessionVersion->isGreaterThan($version)
        );
    }

    /**
     * @return array
     */
    public function isGreaterThanDataProvider(): array
    {
        return [
            ['2.2', '2.1.9', false],
            ['2.2', '2.2', false],
            ['2.2', '2.2.0', true],
            ['2.2.0', '2.2.1', true],
            ['2.2', '2.2-dev', false],
            ['2.2-dev', '2.2.1-dev', true],
        ];
    }
}
