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
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->searchEngineValidator = new SearchEngine(
            $this->environmentMock,
            $this->magentoVersionMock,
            $this->resultFactoryMock
        );
    }

    public function testConfigValid()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'elasticsearch' => [['host' => '127.0.0.1']],
                'database' => [
                    [
                        'host' => 'database.internal',
                        'scheme' => 'mysql',
                    ],
                ],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $this->searchEngineValidator->validate();
    }

    public function testConfigSolr21()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'solr' => [['host' => '127.0.0.1']],
                'database' => [
                    [
                        'host' => 'database.internal',
                        'scheme' => 'mysql',
                    ],
                ],
            ]);
        $this->magentoVersionMock->method('satisfies')
            ->willReturnMap([
                ['2.1.*', true],
                ['>=2.2', false],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ResultInterface::ERROR,
                [
                    'error' => 'Configuration for Solr was found in .magento.app.yaml.',
                    'suggestion' => 'Solr support has been deprecated in Magento 2.1. ' .
                        'Update your search engine to Elasticsearch and remove this relationship.',
                ]
            )->willReturn($this->createMock(Error::class));

        $this->searchEngineValidator->validate();
    }

    public function testConfigSolr22()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'solr' => [['host' => '127.0.0.1']],
                'database' => [
                    [
                        'host' => 'database.internal',
                        'scheme' => 'mysql',
                    ],
                ],
            ]);

        $this->magentoVersionMock->method('satisfies')
            ->willReturnMap([
                ['2.1.*', false],
                ['>=2.2', true],
            ]);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ResultInterface::ERROR,
                [
                    'error' => 'Configuration for Solr was found in .magento.app.yaml.',
                    'suggestion' => 'Solr is no longer supported by Magento 2.2 or later. ' .
                        'Remove this relationship and use Elasticsearch.',
                ]
            )->willReturn($this->createMock(Error::class));

        $this->searchEngineValidator->validate();
    }
}
