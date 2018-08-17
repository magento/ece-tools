<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\Magento\System;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * {@inheritdoc}
 */
class SystemTest extends TestCase
{
    /**
     * @var System
     */
    private $config;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->config = new System(
            $this->shellMock,
            $this->magentoVersionMock
        );
    }

    /**
     * @param mixed $expectedResult
     * @dataProvider getDataProvider
     * @throws UndefinedPackageException
     */
    public function testValidate($expectedResult)
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.0')
            ->willReturn(true);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('./bin/magento config:show \'some/key\'')
            ->willReturn([$expectedResult]);

        $this->assertSame($expectedResult, $this->config->get('some/key'));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            ['some'],
            ['0'],
            ['1'],
        ];
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetDefaultValue()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.0')
            ->willReturn(true);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('./bin/magento config:show \'some/key\'')
            ->willThrowException(new \Exception('Command bin/magento returned code 1', 1));

        $this->assertNull($this->config->get('some/key'));
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetLegacyVersion()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.0')
            ->willReturn(false);
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->assertNull($this->config->get('some/key'));
    }
}
