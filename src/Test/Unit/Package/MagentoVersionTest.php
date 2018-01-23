<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Package;

use Composer\Package\PackageInterface;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class MagentoVersionTest extends TestCase
{
    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $managerMock;

    /**
     * @var PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $packageMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->managerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->packageMock = $this->getMockBuilder(PackageInterface::class)
            ->getMockForAbstractClass();

        $this->magentoVersion = new MagentoVersion(
            $this->managerMock,
            new Comparator(),
            new Semver()
        );
    }

    /**
     * @param string $version
     * @param string $packageVersion
     * @param bool $expected
     * @dataProvider isGreaterOrEqualDataProvider
     */
    public function testIsGreaterOrEqual(string $version, string $packageVersion, bool $expected)
    {
        $this->managerMock->method('get')
            ->with('magento/magento2-base')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($packageVersion);

        $this->assertSame(
            $expected,
            $this->magentoVersion->isGreaterOrEqual($version)
        );
    }

    /**
     * @return array
     */
    public function isGreaterOrEqualDataProvider(): array
    {
        return [
            ['2.2', '2.1.9', false],
            ['2.2', '2.2', true],
            ['2.2', '2.2.0', true],
            ['2.2.0', '2.2.0', true],
            ['2.2', '2.2-dev', false],
            ['2.2-dev', '2.2-dev', true],
        ];
    }

    /**
     * Test getting the version number from the installed version of Magento.
     */
    public function testGetVersion()
    {
        $this->managerMock->method('get')
            ->with('magento/magento2-base')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.2.1');
        
        $this->assertSame('2.2.1', $this->magentoVersion->getVersion());
    }

    /**
     * Test the constraint matcher using various Composer-style version constraints.
     */
    public function testSatisfies()
    {
        $this->managerMock->expects($this->exactly(6))
            ->method('get')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->exactly(6))
            ->method('getVersion')
            ->willReturn('2.2.1');

        foreach (['2.2.1', '2.2.*', '~2.2.0'] as $constraint) {
            $this->assertTrue($this->magentoVersion->satisfies($constraint));
        }

        foreach (['2.2.0', '2.1.*', '~2.1.0'] as $constraint) {
            $this->assertFalse($this->magentoVersion->satisfies($constraint));
        }
    }
}
