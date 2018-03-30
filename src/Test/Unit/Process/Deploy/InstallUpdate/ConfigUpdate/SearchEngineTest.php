<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Http\Message\StreamInterface;
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
     * @var EnvWriter|Mock
     */
    private $envWriterMock;

    /**
     * @var SharedWriter|Mock
     */
    private $sharedWriterMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var ClientFactory|Mock
     */
    private $clientFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->envWriterMock = $this->createMock(EnvWriter::class);
        $this->sharedWriterMock = $this->createMock(SharedWriter::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);

        $this->process = new SearchEngine(
            $this->environmentMock,
            $this->loggerMock,
            $this->envWriterMock,
            $this->sharedWriterMock,
            $this->stageConfigMock,
            $this->magentoVersionMock,
            $this->clientFactoryMock
        );
    }

    public function testExecute()
    {
        $config['system']['default']['catalog']['search'] = ['engine' => 'mysql'];
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([]);
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->envWriterMock->expects($this->once())
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

    /**
     * @return array
     */
    public function magentoVersionTestDataProvider(): array
    {
        return [
            ['newVersion' => true],
            ['newVersion' => false],
        ];
    }

    /**
     * @param bool newVersion
     * @param string $version
     * @param array $relationships
     * @param array $expected
     * @dataProvider executeWithElasticSearchDataProvider
     */
    public function testExecuteWithElasticSearch(
        bool $newVersion,
        string $version,
        array $relationships,
        array $expected
    ) {
        $config['system']['default']['catalog']['search'] = $expected;

        $clientMock = $this->getMockBuilder(Client::class)
            ->setMethods(['get'])
            ->getMock();
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);

        $clientMock->expects($this->once())
            ->method('get')
            ->with($relationships['host'] . ':' . $relationships['port'])
            ->willReturn($responseMock);
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);
        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn('{
                "name" : "ZaIj9mo",
                "cluster_name" : "elasticsearch",
                "cluster_uuid" : "CIXBGIVdS6mwM_0lmVhF4g",
                "version" : {
                    "number" : "' . $version . '",
                    "build_hash" : "c59ff00",
                    "build_date" : "2018-03-13T10:06:29.741383Z",
                    "build_snapshot" : false,
                    "lucene_version" : "7.2.1",
                    "minimum_wire_compatibility_version" : "5.6.0",
                    "minimum_index_compatibility_version" : "5.0.0"
                },
                "tagline" : "You Know, for Search"
            }
        ');

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn($newVersion);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(
                [
                    'elasticsearch' => [
                        $relationships,
                    ],
                ]
            );
        if ($newVersion) {
            $this->envWriterMock->expects($this->once())
                ->method('update')
                ->with($config);
        } else {
            $this->sharedWriterMock->expects($this->once())
                ->method('update')
                ->with($config);
        }
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: ' . $expected['engine']]
            );

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeWithElasticSearchDataProvider(): array
    {
        return [
            [
                'newVersion' => true,
                'version' => '2.4',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch',
                    'elasticsearch_server_hostname' => 'localhost',
                    'elasticsearch_server_port' => 1234,
                ],
            ],
            [
                'newVersion' => true,
                'version' => '5',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'localhost',
                    'elasticsearch5_server_port' => 1234,
                ],
            ],
            [
                'newVersion' => false,
                'version' => '5.1',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'localhost',
                    'elasticsearch5_server_port' => 1234,
                ],
            ],
            [
                'newVersion' => false,
                'version' => '6.2',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'localhost',
                    'elasticsearch5_server_port' => 1234,
                ],
            ],
        ];
    }

    public function testExecuteWithElasticSearchException()
    {
        $relationships = [
            'host' => 'localhost',
            'port' => 1234,
        ];
        $expected = [
            'engine' => 'elasticsearch',
            'elasticsearch_server_hostname' => 'localhost',
            'elasticsearch_server_port' => 1234,
        ];

        $config['system']['default']['catalog']['search'] = $expected;

        $clientMock = $this->getMockBuilder(Client::class)
            ->setMethods(['get'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('get')
            ->with($relationships['host'] . ':' . $relationships['port'])
            ->willThrowException(new \RuntimeException('ES is not available'));

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
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
                        $relationships,
                    ],
                ]
            );
        $this->envWriterMock->expects($this->once())
            ->method('update')
            ->with($config);

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: ' . $expected['engine']]
            );
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('ES is not available');

        $this->process->execute();
    }

    /**
     * @param bool newVersion
     * @dataProvider magentoVersionTestDataProvider
     */
    public function testExecuteWithElasticSolr(bool $newVersion)
    {
        $config['system']['default']['catalog']['search'] = [
            'engine' => 'solr',
            'solr_server_hostname' => 'localhost',
            'solr_server_port' => 1234,
            'solr_server_username' => 'scheme',
            'solr_server_path' => 'path',
        ];

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn($newVersion);

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
        if ($newVersion) {
            $this->envWriterMock->expects($this->once())
                ->method('update')
                ->with($config);
        } else {
            $this->sharedWriterMock->expects($this->once())
                ->method('update')
                ->with($config);
        }
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: solr']
            );

        $this->process->execute();
    }

    /**
     * @param bool newVersion
     * @dataProvider magentoVersionTestDataProvider
     */
    public function testExecuteEnvironmentConfiguration(bool $newVersion)
    {
        $config['system']['default']['catalog']['search'] = [
            'engine' => 'elasticsearch',
            'elasticsearch_server_hostname' => 'elasticsearch_host',
            'elasticsearch_server_port' => 'elasticsearch_port',
        ];

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn($newVersion);

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
        if ($newVersion) {
            $this->envWriterMock->expects($this->once())
                ->method('update')
                ->with($config);
        } else {
            $this->sharedWriterMock->expects($this->once())
                ->method('update')
                ->with($config);
        }
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: elasticsearch']
            );

        $this->process->execute();
    }
}
