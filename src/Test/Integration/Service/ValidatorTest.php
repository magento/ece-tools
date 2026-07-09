<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Integration\Service;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @param string $magentoVersion
     * @dataProvider rabbitMq42SupportedDataProvider
     */
    #[DataProvider('rabbitMq42SupportedDataProvider')]
    public function testRabbitMq42IsSupported(string $magentoVersion): void
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);
        $magentoVersionMock->method('getVersion')->willReturn($magentoVersion);

        $validator = new Validator($magentoVersionMock);

        $this->assertSame(
            '',
            $validator->validateService(ServiceInterface::NAME_RABBITMQ, '4.2.0')
        );
    }

    /**
     * @param string $magentoVersion
     * @dataProvider rabbitMq43SupportedDataProvider
     */
    #[DataProvider('rabbitMq43SupportedDataProvider')]
    public function testRabbitMq43IsSupported(string $magentoVersion): void
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);
        $magentoVersionMock->method('getVersion')->willReturn($magentoVersion);

        $validator = new Validator($magentoVersionMock);

        $this->assertSame(
            '',
            $validator->validateService(ServiceInterface::NAME_RABBITMQ, '4.3.0')
        );
    }

    /**
     * @param string $magentoVersion
     * @dataProvider rabbitMq43NotSupportedDataProvider
     */
    #[DataProvider('rabbitMq43NotSupportedDataProvider')]
    public function testRabbitMq43IsNotSupported(string $magentoVersion): void
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);
        $magentoVersionMock->method('getVersion')->willReturn($magentoVersion);

        $validator = new Validator($magentoVersionMock);
        $message = $validator->validateService(ServiceInterface::NAME_RABBITMQ, '4.3.0');

        $this->assertStringContainsString('does not support version "4.3.0"', $message);
    }

    public static function rabbitMq42SupportedDataProvider(): array
    {
        return [
            ['2.4.5-p17'],
            ['2.4.6-p15'],
            ['2.4.7-p10'],
            ['2.4.8-p5'],
            ['2.4.9'],
        ];
    }

    public static function rabbitMq43SupportedDataProvider(): array
    {
        return [
            ['2.4.5-p17'],
            ['2.4.6-p15'],
            ['2.4.7-p10'],
            ['2.4.8-p5'],
            ['2.4.9'],
        ];
    }

    public static function rabbitMq43NotSupportedDataProvider(): array
    {
        return [
            ['2.4.5-p16'],
            ['2.4.6-p14'],
            ['2.4.7-p9'],
            ['2.4.8'],
            ['2.4.8-p4'],
        ];
    }
}
