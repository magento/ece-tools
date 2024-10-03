<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\ServiceMismatchException;
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
    protected function setUp(): void
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->validator = new Validator($this->magentoVersionMock);
    }

    /**
     * @param string $magentoVersion
     * @param array $versions
     * @param int $errorsNumber
     * @throws UndefinedPackageException
     * @throws ServiceMismatchException
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
     * @throws UndefinedPackageException
     * @throws \Magento\MagentoCloud\Service\ServiceMismatchException
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
     * @throws UndefinedPackageException
     * @throws ServiceMismatchException
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
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                    ServiceInterface::NAME_DB_MARIA => '10.0',
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
                    ServiceInterface::NAME_PHP => '7.2.13', // wrong
                    ServiceInterface::NAME_DB_MARIA => '10.6.1',
                    ServiceInterface::NAME_NGINX => '1.9',
                    ServiceInterface::NAME_VARNISH => '7.1.1',
                    ServiceInterface::NAME_REDIS => 'latest',
                    ServiceInterface::NAME_ELASTICSEARCH => '6.7', // wrong
                    ServiceInterface::NAME_RABBITMQ => '3.7' // wrong
                ],
                3
            ],
            [
                '2.4.0',
                [
                    ServiceInterface::NAME_PHP => '7.4',
                    ServiceInterface::NAME_DB_MARIA => '10.4',
                    ServiceInterface::NAME_NGINX => '1.9',
                    ServiceInterface::NAME_VARNISH => '6.2',
                    ServiceInterface::NAME_REDIS => '5.0',
                    ServiceInterface::NAME_ELASTICSEARCH => '6.8', // wrong
                    ServiceInterface::NAME_RABBITMQ => '3.8'
                ],
                1
            ],
            [
                '2.4.0',
                [
                    ServiceInterface::NAME_PHP => '7.4',
                    ServiceInterface::NAME_DB_MARIA => '10.4',
                    ServiceInterface::NAME_NGINX => '1.9',
                    ServiceInterface::NAME_VARNISH => '6.2',
                    ServiceInterface::NAME_REDIS => '5.0',
                    ServiceInterface::NAME_ELASTICSEARCH => '7.5',
                    ServiceInterface::NAME_RABBITMQ => '3.8'
                ],
                0
            ],
            [
                '2.4.1',
                [
                    ServiceInterface::NAME_PHP => '7.4',
                    ServiceInterface::NAME_DB_MARIA => '10.4',
                    ServiceInterface::NAME_NGINX => '1.9',
                    ServiceInterface::NAME_VARNISH => '6.2',
                    ServiceInterface::NAME_REDIS => '5.0',
                    ServiceInterface::NAME_ELASTICSEARCH => '7.9', // wrong
                    ServiceInterface::NAME_RABBITMQ => '3.8'
                ],
                1
            ],
            [
                '2.4.1',
                [
                    ServiceInterface::NAME_DB_MYSQL => '5.6',
                ],
                1
            ],
            [
                '2.4.1',
                [
                    ServiceInterface::NAME_DB_MYSQL => '8.0.2',
                ],
                0
            ],
            [
                '2.4.1',
                [
                    ServiceInterface::NAME_DB_MYSQL => '8.0',
                ],
                0
            ],
            [
                '2.4.2',
                [
                    ServiceInterface::NAME_PHP => '7.4',
                    ServiceInterface::NAME_DB_MARIA => '10.4',
                    ServiceInterface::NAME_NGINX => '1.9',
                    ServiceInterface::NAME_VARNISH => '6.2',
                    ServiceInterface::NAME_REDIS => '5.0',
                    ServiceInterface::NAME_ELASTICSEARCH => '7.9',
                    ServiceInterface::NAME_RABBITMQ => '3.8'
                ],
                0
            ],
            [
                '2.3.6',
                [
                    ServiceInterface::NAME_PHP => '7.4', // wrong
                    ServiceInterface::NAME_DB_MARIA => '10.3', // wrong
                    ServiceInterface::NAME_NGINX => '1.19',
                    ServiceInterface::NAME_VARNISH => '6.2',
                    ServiceInterface::NAME_REDIS => '5.0',
                    ServiceInterface::NAME_ELASTICSEARCH => '7.9', //wrong
                ],
                3
            ],
            [
                '2.3.6-p1',
                [
                    ServiceInterface::NAME_DB_MARIA => '10.3',
                ],
                0
            ],
            [
                '2.3.6-p1',
                [
                    ServiceInterface::NAME_DB_MARIA => '10.4', // wrong
                ],
                1
            ],
            [
                '2.3.7',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.9',
                ],
                0
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
                    ServiceInterface::NAME_DB_MARIA => '11.0', //wrong
                    ServiceInterface::NAME_NGINX => '0.9', //wrong
                    ServiceInterface::NAME_VARNISH => '4.0.9',
                    ServiceInterface::NAME_REDIS => '3.1',
                    ServiceInterface::NAME_ELASTICSEARCH => '6.5', //wrong
                    ServiceInterface::NAME_RABBITMQ => '3.5' //wrong
                ],
                4
            ],
            [
                '2.3.7',
                [
                    ServiceInterface::NAME_OPENSEARCH => '1.1' //wrong
                ],
                1
            ],
            [
                '2.3.7-p2',
                [
                    ServiceInterface::NAME_OPENSEARCH => '1.2' //wrong
                ],
                1
            ],
            [
                '2.3.7-p3',
                [
                    ServiceInterface::NAME_OPENSEARCH => '1.1'
                ],
                0
            ],
            [
                '2.3.7-p4',
                [
                    ServiceInterface::NAME_PHP => '7.4.0',
                    ServiceInterface::NAME_DB_MARIA => '10.3.35',
                    ServiceInterface::NAME_NGINX => '1.18.0',
                    ServiceInterface::NAME_VARNISH => '6.5.1',
                    ServiceInterface::NAME_REDIS => '6.0.12',
                    ServiceInterface::NAME_OPENSEARCH => '1.2',
                    ServiceInterface::NAME_RABBITMQ => '3.9.0'
                ],
                0
            ],
            [
                '2.3.7-p3',
                [
                    ServiceInterface::NAME_OPENSEARCH => '1.2.2'
                ],
                0
            ],
            [
                '2.4.0',
                [
                    ServiceInterface::NAME_OPENSEARCH => '1.2' //wrong
                ],
                1
            ],
            [
                '2.4.3-p2',
                [
                    ServiceInterface::NAME_OPENSEARCH => '1.2'
                ],
                0
            ],
            [
                '2.4.3',
                [
                    ServiceInterface::NAME_OPENSEARCH => '1.2.2' //wrong
                ],
                1
            ],
            [
                '2.4.4',
                [
                    ServiceInterface::NAME_OPENSEARCH => '1.2.2',
                    ServiceInterface::NAME_REDIS => '6.2',
                    ServiceInterface::NAME_REDIS_SESSION => '6.2',
                    ServiceInterface::NAME_RABBITMQ => '3.9'
                ],
                0
            ],
            [
                '2.3.7-p2',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10' //wrong
                ],
                1
            ],
            [
                '2.3.7-p3',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10'
                ],
                0
            ],
            [
                '2.4.0',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10' //wrong
                ],
                1
            ],
            [
                '2.4.1',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10' //wrong
                ],
                1
            ],
            [
                '2.4.2',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10' //wrong
                ],
                1
            ],
            [
                '2.4.3',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10'
                ],
                0
            ],
            [
                '2.4.4',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10'
                ],
                0
            ],
            [
                '2.4.3-p3',
                [
                    ServiceInterface::NAME_PHP => '7.4.0',
                    ServiceInterface::NAME_DB_MARIA => '10.4.14',
                    ServiceInterface::NAME_NGINX => '1.18.0',
                    ServiceInterface::NAME_VARNISH => '6.5.1',
                    ServiceInterface::NAME_REDIS => '6.0.12',
                    ServiceInterface::NAME_OPENSEARCH => '1.2',
                    ServiceInterface::NAME_RABBITMQ => '3.8.0'
                ],
                0
            ],
            [
                '2.4.3-p3',
                [
                    ServiceInterface::NAME_RABBITMQ => '3.9.0'
                ],
                0
            ],
            [
                '2.4.4-p1',
                [
                    ServiceInterface::NAME_PHP => '8.1.0',
                    ServiceInterface::NAME_DB_MARIA => '10.4.22',
                    ServiceInterface::NAME_NGINX => '1.18.0',
                    ServiceInterface::NAME_VARNISH => '7.0.0',
                    ServiceInterface::NAME_REDIS => '6.2.6',
                    ServiceInterface::NAME_OPENSEARCH => '1.2',
                    ServiceInterface::NAME_RABBITMQ => '3.9.0'
                ],
                0
            ],
            [
                '2.4.5',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10'
                ],
                0
            ],
            [
                '2.4.5',
                [
                    ServiceInterface::NAME_PHP => '8.0.0', //wrong
                    ServiceInterface::NAME_ELASTICSEARCH => '7.11.0', //wrong
                    ServiceInterface::NAME_DB_MARIA => '10.5.0', //wrong
                    ServiceInterface::NAME_VARNISH => '7.1.0', //wrong
                    ServiceInterface::NAME_REDIS => '6.1.3', //wrong
                    ServiceInterface::NAME_OPENSEARCH => '1.0.0', //wrong
                    ServiceInterface::NAME_RABBITMQ => '3.8.1' //wrong
                ],
                6
            ],
            [
                '2.4.5',
                [
                    ServiceInterface::NAME_PHP => '8.1.0',
                    ServiceInterface::NAME_DB_MARIA => '10.4.22',
                    ServiceInterface::NAME_NGINX => '1.18.0',
                    ServiceInterface::NAME_VARNISH => '7.0.0',
                    ServiceInterface::NAME_REDIS => '6.2.6',
                    ServiceInterface::NAME_OPENSEARCH => '1.2',
                    ServiceInterface::NAME_RABBITMQ => '3.9.0'
                ],
                0
            ],
            [
                '2.4.6',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.11' //wrong
                ],
                1
            ],
            [
                '2.4.6',
                [
                    ServiceInterface::NAME_PHP => '8.2.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6.0',
                    ServiceInterface::NAME_VARNISH => '7.1.1',
                    ServiceInterface::NAME_REDIS => '7.0.0',
                    ServiceInterface::NAME_OPENSEARCH => '2.3.0',
                    ServiceInterface::NAME_RABBITMQ => '3.9.0'
                ],
                0
            ],
            [
                '2.4.6',
                [
                    ServiceInterface::NAME_PHP => '8.2.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6.0',
                    ServiceInterface::NAME_VARNISH => '7.1.1',
                    ServiceInterface::NAME_REDIS => '7.0.0',
                    ServiceInterface::NAME_OPENSEARCH => '2.3.0',
                    ServiceInterface::NAME_RABBITMQ => '3.11.0'
                ],
                0
            ],
            [
                '2.4.6',
                [
                    ServiceInterface::NAME_PHP => '8.2.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6.0',
                    ServiceInterface::NAME_VARNISH => '7.1.1',
                    ServiceInterface::NAME_REDIS => '7.0.0',
                    ServiceInterface::NAME_OPENSEARCH => '2.3.0',
                    ServiceInterface::NAME_RABBITMQ => '3.8.0' // wrong
                ],
                1
            ],
            [
                '2.4.7',
                [
                    ServiceInterface::NAME_PHP => '8.2.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6.0',
                    ServiceInterface::NAME_VARNISH => '7.1.1',
                    ServiceInterface::NAME_REDIS => '7.2.0',
                    ServiceInterface::NAME_OPENSEARCH => '2.3.0',
                    ServiceInterface::NAME_RABBITMQ => '3.9.0' // wrong
                ],
                1
            ],
            [
                '2.4.7',
                [
                    ServiceInterface::NAME_PHP => '8.2.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6.0',
                    ServiceInterface::NAME_VARNISH => '7.1.1',
                    ServiceInterface::NAME_REDIS => '7.2.0',
                    ServiceInterface::NAME_OPENSEARCH => '2.3.0',
                    ServiceInterface::NAME_RABBITMQ => '3.12.0'
                ],
                0
            ],
            [
                '2.4.7',
                [
                    ServiceInterface::NAME_RABBITMQ => '3.13.0'
                ],
                0
            ],
        ];
    }
}
