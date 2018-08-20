<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\ElasticSearchUsage;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config as SearchEngineConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ElasticSearchUsageTest extends TestCase
{
    /**
     * @var ElasticSearchUsage
     */
    private $validator;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var SearchEngineConfig|MockObject
     */
    private $searchEngineConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->searchEngineConfigMock = $this->createMock(SearchEngineConfig::class);
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);

        $this->validator = new ElasticSearchUsage(
            $this->environmentMock,
            $this->searchEngineConfigMock,
            $this->resultFactoryMock
        );
    }

    /**
     * @param array $relationships
     * @param array $searchConfig
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $relationships, array $searchConfig, string $expectedResultClass)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn($relationships);
        $this->searchEngineConfigMock->expects($this->any())
            ->method('get')
            ->willReturn($searchConfig);

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            'elasticsearch service is not installed' => [
                [],
                ['engine' => 'elasticsearch'],
                Success::class,
            ],
            'elasticsearch5 service is not installed' => [
                [],
                ['engine' => 'elasticsearch5'],
                Success::class,
            ],
            'elasticsearch service is installed and elasticsearch used as search engine' => [
                ['elasticsearch' => ['some_config']],
                ['engine' => 'elasticsearch'],
                Success::class,
            ],
            'elasticsearch5 service is installed and elasticsearch used as search engine' => [
                ['elasticsearch' => ['some_config']],
                ['engine' => 'elasticsearch5'],
                Success::class,
            ],
            'elasticsearch service is installed and elasticsearch don\'t used as search engine' => [
                ['elasticsearch' => []],
                ['engine' => 'mysql'],
                Error::class,
            ],
        ];
    }
}
