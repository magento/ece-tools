<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\SearchEngine;
use Magento\MagentoCloud\Config\Validator\Deploy\ElasticSuiteIntegrity;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSearch;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ElasticSuiteIntegrityTest extends TestCase
{
    /**
     * @var ElasticSuiteIntegrity
     */
    private $validator;

    /**
     * @var ElasticSuite|MockObject
     */
    private $elasticSuiteMock;

    /**
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var SearchEngine|MockObject
     */
    private $searchEngineMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->elasticSuiteMock = $this->createMock(ElasticSuite::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->searchEngineMock = $this->createMock(SearchEngine::class);

        $this->validator = new ElasticSuiteIntegrity(
            $this->elasticSuiteMock,
            $this->elasticSearchMock,
            $this->resultFactoryMock,
            $this->searchEngineMock
        );
    }

    public function testValidate()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateNoESInstalled()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('ElasticSuite is installed without available ElasticSearch service.')
            ->willReturn(new Error('Some error'));

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    public function testValidateSearchEngineIsMysql()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->searchEngineMock->expects($this->once())
            ->method('getName')
            ->willReturn('mysql');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('ElasticSuite is installed but mysql set as search engine.')
            ->willReturn(new Error('Some error'));

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    public function testValidateNoErrors()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->searchEngineMock->expects($this->once())
            ->method('getName')
            ->willReturn(ElasticSuite::ENGINE_NAME);
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }
}
