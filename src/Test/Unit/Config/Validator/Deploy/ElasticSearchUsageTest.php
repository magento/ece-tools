<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\ElasticSearchUsage;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\SearchEngine;
use Magento\MagentoCloud\Service\ElasticSearch;
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
     * @var SearchEngine|MockObject
     */
    private $searchEngineConfigMock;

    /**
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->searchEngineConfigMock = $this->createMock(SearchEngine::class);
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);

        $this->validator = new ElasticSearchUsage(
            $this->searchEngineConfigMock,
            $this->resultFactoryMock,
            $this->elasticSearchMock
        );
    }

    /**
     * @param bool $isInstalled
     * @param bool $isESFamily
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     */
    public function testValidate(bool $isInstalled, bool $isESFamily, string $expectedResultClass)
    {
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn($isInstalled);
        $this->searchEngineConfigMock->method('isESFamily')
            ->willReturn($isESFamily);

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            'ES is not installed' => [
                false,
                false,
                Success::class,
            ],
            'engine is not ES' => [
                true,
                true,
                Success::class,
            ],
            'ES installed without usage' => [
                true,
                false,
                Error::class,
            ],
        ];
    }
}
