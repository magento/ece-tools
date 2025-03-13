<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\ElasticSuiteIntegrity;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\OpenSearch;
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
     * @var OpenSearch|MockObject
     */
    private $openSearchMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->elasticSuiteMock = $this->createMock(ElasticSuite::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->openSearchMock = $this->createMock(OpenSearch::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);

        $this->validator = new ElasticSuiteIntegrity(
            $this->elasticSuiteMock,
            $this->elasticSearchMock,
            $this->openSearchMock,
            $this->resultFactoryMock,
            $this->stageConfigMock
        );
    }

    public function testValidate()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->elasticSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->openSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateEsOs()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->openSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateEs()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->openSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateOs()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->openSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateNoESandOSInstalled()
    {
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->openSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'ElasticSuite is installed without available ElasticSearch or OpenSearch service.',
                '',
                AppError::DEPLOY_ELASTIC_SUITE_WITHOUT_ES
            )
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
        $this->openSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn(['engine' => 'mysql']);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'ElasticSuite is installed but mysql set as search engine.',
                '',
                AppError::DEPLOY_ELASTIC_SUITE_WRONG_ENGINE
            )
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
        $this->openSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn(['engine' => ElasticSuite::ENGINE_NAME]);
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }
}
