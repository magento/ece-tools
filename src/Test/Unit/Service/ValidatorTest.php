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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
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
     * Test validate versions.
     *
     * @param string $magentoVersion
     * @param array $versions
     * @param int $errorsNumber
     * @dataProvider validateVersionsDataProvider
     * @return void
     * @throws UndefinedPackageException
     * @throws ServiceMismatchException
     *
     */
    #[DataProvider('validateVersionsDataProvider')]
    public function testValidateVersions(string $magentoVersion, array $versions, int $errorsNumber = 0): void
    {
        $this->magentoVersionMock->method('getVersion')
            ->willReturn($magentoVersion);

        $this->assertEquals(
            $errorsNumber,
            count($this->validator->validateVersions($versions))
        );
    }

    /**
     * Test validate fail message.
     *
     * @return void
     * @throws UndefinedPackageException
     * @throws ServiceMismatchException
     */
    public function testValidateFailMessage(): void
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
     * Test validate nonexistent service.
     *
     * @return void
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
        $this->magentoVersionMock->method('getVersion')
            ->willReturn($magentoVersion);

        $this->assertEquals(
            [$message],
            $this->validator->validateVersions([$serviceName => '1.1'])
        );
    }

    /**
     * Data provider for validate versions.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function validateVersionsDataProvider(): array
    {
        return [
            [
                '2.4.4',
                [
                    ServiceInterface::NAME_OPENSEARCH => '2.3.0',
                    ServiceInterface::NAME_REDIS => '6.2',
                    ServiceInterface::NAME_REDIS_SESSION => '6.2',
                    ServiceInterface::NAME_RABBITMQ => '3.9'
                ],
                1
            ],
            [
                '2.4.4',
                [
                    ServiceInterface::NAME_ELASTICSEARCH => '7.10'
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
                '2.4.4-p17',
                [
                    ServiceInterface::NAME_PHP => '8.1.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6',
                    ServiceInterface::NAME_NGINX => '1.22.0',
                    ServiceInterface::NAME_VARNISH => '7.0.0',
                    ServiceInterface::NAME_REDIS => '7.2',
                    ServiceInterface::NAME_OPENSEARCH => '2.19',
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
                    ServiceInterface::NAME_OPENSEARCH => '2.3.0', //wrong
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
            [
                '2.4.4-p12',
                [
                    ServiceInterface::NAME_PHP => '8.1.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6.0',
                    ServiceInterface::NAME_NGINX => '1.18.0',
                    ServiceInterface::NAME_VARNISH => '7.0.0',
                    ServiceInterface::NAME_REDIS => '6.2.6',
                    ServiceInterface::NAME_OPENSEARCH => '1.3.0',
                    ServiceInterface::NAME_RABBITMQ => '3.9.0'
                ],
                0
            ],
            [
                '2.4.5-p11',
                [
                    ServiceInterface::NAME_PHP => '8.1.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6.0',
                    ServiceInterface::NAME_NGINX => '1.18.0',
                    ServiceInterface::NAME_VARNISH => '7.0.0',
                    ServiceInterface::NAME_REDIS => '6.2.6',
                    ServiceInterface::NAME_OPENSEARCH => '1.3.0',
                    ServiceInterface::NAME_RABBITMQ => '3.11.0'
                ],
                0
            ],
            [
                '2.4.5-p12',
                [
                    ServiceInterface::NAME_PHP => '8.1.0',
                    ServiceInterface::NAME_DB_MARIA => '10.6.0',
                    ServiceInterface::NAME_NGINX => '1.18.0',
                    ServiceInterface::NAME_VARNISH => '7.0.0',
                    ServiceInterface::NAME_REDIS => '6.2.6',
                    ServiceInterface::NAME_OPENSEARCH => '2.0.0',
                    ServiceInterface::NAME_RABBITMQ => '3.11.0'
                ],
                0
            ],
            [
                '2.4.5-p13',
                [
                    ServiceInterface::NAME_VALKEY => '8.0.0'
                ],
                0
            ],
            [
                '2.4.6-p11',
                [
                    ServiceInterface::NAME_VALKEY => '8.0.0'
                ],
                0
            ],
            [
                '2.4.7-p6',
                [
                    ServiceInterface::NAME_VALKEY => '8.0.0'
                ],
                0
            ],
            [
               '2.4.8',
                [
                    ServiceInterface::NAME_PHP => '8.4.0'
                ],
                0
            ],
            [
                '2.4.8',
                [
                    ServiceInterface::NAME_PHP => '8.5.0'
                ],
                1
            ],
            [
                '2.4.9-beta1',
                [
                    ServiceInterface::NAME_PHP => '8.5.0',
                    ServiceInterface::NAME_RABBITMQ => '4.1.0'
                ],
                0
            ],
            [
                '2.4.9-beta1',
                [
                    ServiceInterface::NAME_PHP => '8.3.0',
                    ServiceInterface::NAME_RABBITMQ => '4.1.0'
                ],
                1
            ],
        ];
    }
}
