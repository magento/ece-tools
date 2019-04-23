<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker\Service\Version;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Docker\Service\Version\Validator;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->validator = new Validator($this->magentoVersionMock);
    }

    /**
     * @param string $magentoVersion
     * @param array $versions
     * @param int $errorsNumber
     * @throws ConfigurationMismatchException
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     *
     * @dataProvider validateVersionsDataProvider
     */
    public function testValidateVersions(string $magentoVersion, array $versions, int $errorsNumber = 0)
    {
        $this->magentoVersionMock->expects($this->any())
            ->method('getVersion')
            ->willReturn($magentoVersion);
        $this->assertEquals($errorsNumber, count($this->validator->validateVersions($versions)));
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     */
    public function testValidateFailMessage()
    {
        $magentoVersion = '2.2.6';
        $version = '1.7';
        $message = sprintf('Magento %s does not support version "%s" for service "%s".'
            . 'Service version should satisfy "^2.0 || ^5.0" constraint.',
            $magentoVersion,
            $version,
            Config::KEY_ELASTICSEARCH
        );

        $this->magentoVersionMock->expects($this->any())
            ->method('getVersion')
            ->willReturn($magentoVersion);

        $this->assertEquals(
            [$message],
            $this->validator->validateVersions([Config::KEY_ELASTICSEARCH => $version]));
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     */
    public function testValidateNonexistentService()
    {
        $magentoVersion = '2.2.2';
        $serviceName = 'nonexistent';
        $message = sprintf('Service "%s" is not supported for Magento "%s"',
            $serviceName,
            $magentoVersion);
        $this->magentoVersionMock->expects($this->any())
            ->method('getVersion')
            ->willReturn($magentoVersion);

        $this->assertEquals(
            [$message],
            $this->validator->validateVersions([$serviceName => '1.1']));
    }

    /**
     * @return array
     */
    public function validateVersionsDataProvider()
    {
        return [
            [
                '2.1.4',
                []
            ],
            [
                '2.1.4',
                [ Config::KEY_PHP => '7.0.2',]
            ],
            [
                '2.2.2',
                [ Config::KEY_NGINX => 'latest',]
            ],
            [
                '2.2.4',
                [
                    Config::KEY_PHP => '7.0.13',
                    Config::KEY_DB => '10.0',
                    Config::KEY_NGINX => '1.9',
                    Config::KEY_VARNISH => '4.5',
                    Config::KEY_REDIS => '5.0',
                    Config::KEY_ELASTICSEARCH => '2.7',
                    Config::KEY_RABBITMQ => '3.5'
                ]
            ],
            [
                '2.2.8',
                [
                    Config::KEY_ELASTICSEARCH => '6.7',
                ]
            ],
            [
                '2.5.0',
                [
                    Config::KEY_PHP => '7.2.13',
                    Config::KEY_DB => '10.2',
                    Config::KEY_NGINX => '1.9',
                    Config::KEY_VARNISH => '5.5',
                    Config::KEY_REDIS => 'latest',
                    Config::KEY_ELASTICSEARCH => '6.7',
                    Config::KEY_RABBITMQ => '3.7'
                ]
            ],
            [
                '2.1.4',
                [Config::KEY_PHP => '7.0.3'],
                1,
            ],
            [
                '2.2.4',
                [
                    Config::KEY_PHP => '7.0.13',
                    Config::KEY_DB => '11.0', //wrong
                    Config::KEY_NGINX => '0.9', //wrong
                    Config::KEY_VARNISH => '4.5',
                    Config::KEY_REDIS => '3.1',
                    Config::KEY_ELASTICSEARCH => '1.7', //wrong
                    Config::KEY_RABBITMQ => '3.5' //wrong
                ],
                4
            ],
        ];
    }
}
