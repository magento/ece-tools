<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\ElasticSuiteConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSuite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ElasticSuiteConfigurationTest extends TestCase
{
    /**
     * @var ElasticSuiteConfiguration
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
     * @var DeployInterface|MockObject
     */
    private $configMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->elasticSuiteMock = $this->createMock(ElasticSuite::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->configMock = $this->createMock(DeployInterface::class);

        $this->validator = new ElasticSuiteConfiguration(
            $this->elasticSuiteMock,
            $this->elasticSearchMock,
            $this->resultFactoryMock,
            $this->configMock
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
        $this->configMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([
                'engine' => 'mysql'
            ]);
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
        $this->configMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([]);
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }
}
