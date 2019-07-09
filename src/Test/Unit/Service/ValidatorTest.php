<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\Validator;
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
     * @throws UndefinedPackageException
     *
     * @dataProvider validateVersionsDataProvider
     */
    public function testValidateVersions(string $magentoVersion, array $versions, int $errorsNumber = 0)
    {
        $this->magentoVersionMock->method('getVersion')
            ->willReturn($magentoVersion);

        $this->assertEquals($errorsNumber, count($this->validator->validateVersions($versions)));
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    public function testValidateFailMessage()
    {
        $magentoVersion = '2.2.6';
        $version = '6.5';
        $message = sprintf(
            'Magento %s does not support version "%s" for service "%s". '
            . 'Service version should satisfy "~1.7.0 || ~2.4.0 || ~5.2.0" constraint.',
            $magentoVersion,
            $version,
            ServiceInterface::NAME_ELASTICSEARCH
        );

        $this->magentoVersionMock->method('getVersion')
            ->willReturn($magentoVersion);

        $this->assertEquals(
            [$message],
            $this->validator->validateVersions([ServiceInterface::NAME_ELASTICSEARCH => $version])
        );
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    public function testValidateNonexistentService()
    {
        $magentoVersion = '2.2.2';
        $serviceName = 'nonexistent';
        $message = sprintf(
            'Service "%s" is not supported for Magento "%s"',
            $serviceName,
            $magentoVersion
        );
        $this->magentoVersionMock->expects($this->any())
            ->method('getVersion')
            ->willReturn($magentoVersion);

        $this->assertEquals(
            [$message],
            $this->validator->validateVersions([$serviceName => '1.1'])
        );
    }

    /**
     * @return array
     */
    public function validateVersionsDataProvider(): array
    {
        return [
            [
                '2.1.4',
                []
            ],
            [
                '2.1.4',
                [ServiceInterface::NAME_PHP => '7.0.2',]
            ],
            [
                '2.2.2',
                [ServiceInterface::NAME_NGINX => 'latest',]
            ],
            [
                '2.2.4',
                [
                    ServiceInterface::NAME_PHP => '7.0.13',
                    ServiceInterface::NAME_DB => '10.0',
                    ServiceInterface::NAME_NGINX => '1.9',
                    ServiceInterface::NAME_VARNISH => '4.5',
                    ServiceInterface::NAME_REDIS => '5.0',
                    ServiceInterface::NAME_ELASTICSEARCH => '2.4.2',
                    ServiceInterface::NAME_RABBITMQ => '3.5'
                ]
            ],
            [
                '2.2.8',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '6.5.13',
                ]
            ],
            [
                '2.5.0',
                [
                    ServiceInterface::NAME_PHP => '7.2.13',
                    ServiceInterface::NAME_DB => '10.2.1',
                    ServiceInterface::NAME_NGINX => '1.9',
                    ServiceInterface::NAME_VARNISH => '5.5',
                    ServiceInterface::NAME_REDIS => 'latest',
                    ServiceInterface::NAME_ELASTICSEARCH => '6.7', // wrong
                    ServiceInterface::NAME_RABBITMQ => '3.7'
                ],
                1
            ],
            [
                '2.1.4',
                [ServiceInterface::NAME_PHP => '5.6'],
                1,
            ],
            [
                '2.2.4',
                [
                    ServiceInterface::NAME_PHP => '7.0.13',
                    ServiceInterface::NAME_DB => '11.0', //wrong
                    ServiceInterface::NAME_NGINX => '0.9', //wrong
                    ServiceInterface::NAME_VARNISH => '4.0.9',
                    ServiceInterface::NAME_REDIS => '3.1',
                    ServiceInterface::NAME_ELASTICSEARCH => '6.5', //wrong
                    ServiceInterface::NAME_RABBITMQ => '3.5' //wrong
                ],
                4
            ],
        ];
    }
}
