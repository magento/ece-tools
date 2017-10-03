<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;
use Magento\MagentoCloud\Config\Deploy\Writer;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class SearchEngineTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Writer|Mock
     */
    private $writerMock;

    /**
     * @var SearchEngine
     */
    private $process;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->writerMock = $this->createMock(Writer::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->process = new SearchEngine(
            $this->environmentMock,
            $this->loggerMock,
            $this->writerMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([]);
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with(['engine' => 'mysql']);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql']
            );

        $this->process->execute();
    }

    public function testExecuteWithElasticSearch()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(
                [
                    'elasticsearch' => [
                        [
                            'host' => 'localhost',
                            'port' => 1234
                        ]
                    ]
                ]
            );
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with([
                'engine' => 'elasticsearch',
                'elasticsearch_server_hostname' => 'localhost',
                'elasticsearch_server_port' => 1234
            ]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: elasticsearch']
            );

        $this->process->execute();
    }

    public function testExecuteWithElasticSolr()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(
                [
                    'solr' => [
                        [
                            'host' => 'localhost',
                            'port' => 1234,
                            'scheme' => 'scheme',
                            'path' => 'path',
                        ]
                    ]
                ]
            );
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with([
                'engine' => 'solr',
                'solr_server_hostname' => 'localhost',
                'solr_server_port' => 1234,
                'solr_server_username' => 'scheme',
                'solr_server_path' => 'path'
            ]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: solr']
            );

        $this->process->execute();
    }
}
