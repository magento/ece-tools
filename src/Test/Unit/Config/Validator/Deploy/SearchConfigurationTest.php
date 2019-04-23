<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\SearchConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->validator = new SearchConfiguration(
            $this->resultFactoryMock,
            $this->stageConfigMock,
            new ConfigMerger()
        );
    }

    /**
     * @param array $searchConfiguration
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $searchConfiguration, string $expectedResultClass)
    {
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
        ];
    }
}
