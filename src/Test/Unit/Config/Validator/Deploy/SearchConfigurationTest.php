<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\SearchConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SearchConfigurationTest extends TestCase
{
    /**
     * @var SearchConfiguration
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->validator = new SearchConfiguration(
            $this->resultFactoryMock,
            $this->stageConfigMock,
            new ConfigMerger(),
            $this->magentoVersionMock
        );
    }

    public function testErrorCode()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn(['key' => 'value']);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Variable SEARCH_CONFIGURATION is not configured properly',
                'At least engine option must be configured',
                AppError::DEPLOY_WRONG_CONFIGURATION_SEARCH
            );

        $this->validator->validate();
    }

    /**
     * @param array $searchConfiguration
     * @param string $expectedResultClass
     * @param bool $isMagento24plus
     * @dataProvider validateDataProvider
     * @throws ValidatorException
     */
    public function testValidate(array $searchConfiguration, string $expectedResultClass, bool $isMagento24plus = false)
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn($isMagento24plus);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($searchConfiguration);

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            [
                [],
                Success::class,
            ],
            [
                [
                    '_merge' => true
                ],
                Success::class,
            ],
            [
                [
                    'elasticsearh_host' => '9200',
                ],
                Error::class,
            ],
            [

                [
                    'elasticsearh_host' => '9200',
                    '_merge' => true,
                ],
                Success::class,
            ],
            [

                [
                    'engine' => 'mysql',
                ],
                Success::class,
            ],
            [
                [
                    'engine' => 'mysql',
                    '_merge' => true,
                ],
                Success::class,
            ],
            [
                [],
                Success::class,
                true
            ],
            [
                [
                    'engine' => 'mysql',
                    '_merge' => true,
                ],
                Error::class,
                true
            ],
            [
                [
                    'engine' => 'mysql',
                ],
                Error::class,
                true
            ],
            [
                [
                    'engine' => 'elasticsearch',
                ],
                Success::class,
                true
            ],
            [
                [
                    'engine' => 'elasticsearch7',
                ],
                Success::class,
                true
            ],
            [
                [
                    'engine' => 'elasticsuite',
                ],
                Success::class,
                true
            ],
        ];
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateWithException()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('some error');

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn(['engine' => 'elasticsearch']);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willThrowException(new UndefinedPackageException('some error'));

        $this->validator->validate();
    }
}
