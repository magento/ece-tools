<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SearchEngineTest extends TestCase
{
    /**
     * @var SearchEngine
     */
    private $process;

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
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->writerMock = $this->createMock(Writer::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->process = new SearchEngine(
            $this->environmentMock,
            $this->loggerMock,
            $this->writerMock,
            $this->stageConfigMock,
            $this->magentoVersionMock
        );
    }

    public function testExecute()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
        $config['system']['default']['catalog']['search'] = ['engine' => 'mysql'];
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([]);
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with($config);
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
        $config['system']['default']['catalog']['search'] = [
            'engine' => 'elasticsearch',
            'elasticsearch_server_hostname' => 'localhost',
            'elasticsearch_server_port' => 1234,
        ];

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(
                [
                    'elasticsearch' => [
                        [
                            'host' => 'localhost',
                            'port' => 1234,
                        ],
                    ],
                ]
            );
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with($config);
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
        $config['system']['default']['catalog']['search'] = [
            'engine' => 'solr',
            'solr_server_hostname' => 'localhost',
            'solr_server_port' => 1234,
            'solr_server_username' => 'scheme',
            'solr_server_path' => 'path',
        ];

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([]);
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
                        ],
                    ],
                ]
            );
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with($config);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: solr']
            );

        $this->process->execute();
    }

    public function testExecuteEnvironmentConfiguration()
    {
        $config['system']['default']['catalog']['search'] = [
            'engine' => 'elasticsearch',
            'elasticsearch_server_hostname' => 'elasticsearch_host',
            'elasticsearch_server_port' => 'elasticsearch_port',
        ];

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([
                'engine' => 'elasticsearch',
                'elasticsearch_server_hostname' => 'elasticsearch_host',
                'elasticsearch_server_port' => 'elasticsearch_port',
            ]);
        $this->environmentMock->expects($this->never())
            ->method('getRelationships');

        $this->writerMock->expects($this->once())
            ->method('update')
            ->with($config);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: elasticsearch']
            );

        $this->process->execute();
    }
    
    public function testSkipExecute()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);

        $this->magentoVersionMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.1.7');
        
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating search engine configuration is not supported in Magento 2.1.7, skipping.');
        $this->stageConfigMock->expects($this->never())
            ->method('get');
        $this->environmentMock->expects($this->never())
            ->method('getRelationships');
        $this->writerMock->expects($this->never())
            ->method('update');
    
        $this->process->execute();
    }
}
