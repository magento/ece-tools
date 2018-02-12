<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\SearchEngine;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SearchEngineTest extends TestCase
{
    /**
     * @var SearchEngine
     */
    private $searchEngineValidator;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    public function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->searchEngineValidator = new SearchEngine($this->environmentMock, $this->resultFactoryMock);
    }

    public function testConfigValid()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'elasticsearch' => [['host' => '127.0.0.1']],
                'database' => [[
                    'host' => 'database.internal',
                    'scheme' => 'mysql',
                ]]]);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $result = $this->searchEngineValidator->validate();

        $this->assertInstanceOf(Success::class, $result);
    }

    public function testConfigSolr()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'solr' => [['host' => '127.0.0.1']],
                'database' => [[
                    'host' => 'database.internal',
                    'scheme' => 'mysql',
                ]]]);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ResultInterface::ERROR,
                [
                    'error' => 'Configuration for Solr was found in .magento.app.yaml.',
                    'suggestion' => 'Solr is no longer supported by Magento 2.1 or later. ' .
                        'You should remove this relationship and use either MySQL or Elasticsearch.',
                ]
            )->willReturn($this->createMock(Error::class));

        $result = $this->searchEngineValidator->validate();

        $this->assertInstanceOf(Error::class, $result);
    }
}
