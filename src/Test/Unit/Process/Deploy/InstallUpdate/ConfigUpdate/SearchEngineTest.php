<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Environment\Type as EnvironmentType;
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
     * @var EnvironmentType|Mock
     */
    private $environmentTypeMock;

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
        $this->environmentTypeMock = $this->createMock(EnvironmentType::class);

        $this->process = new SearchEngine(
            $this->environmentMock,
            $this->loggerMock,
            $this->envWriterMock,
            $this->sharedWriterMock,
            $this->stageConfigMock,
            $this->magentoVersionMock,
            $this->environmentTypeMock
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
            ['newVersion' => true, EnvironmentType::PRODUCTION, []],
            ['newVersion' => false, EnvironmentType::PRODUCTION, []],
            ['newVersion' => true, 'test', ['elasticsearch_index_prefix' => 'magento2_test']],
            ['newVersion' => true, EnvironmentType::STAGING, ['elasticsearch_index_prefix' => 'magento2_staging']],
            [
                'newVersion' => true,
                EnvironmentType::INTEGRATION,
                ['elasticsearch_index_prefix' => 'magento2_integration']
            ],
        ];
    }

    /**
     * @param bool $newVersion
     * @param string $environmentType
     * @param array $additionalOptions
     * @dataProvider magentoVersionTestDataProvider
     */
    public function testExecuteWithElasticSearch(bool $newVersion, string $environmentType, array $additionalOptions)
    {
        $config['system']['default']['catalog']['search'] = array_merge([
            'engine' => 'elasticsearch',
            'elasticsearch_server_hostname' => 'localhost',
            'elasticsearch_server_port' => 1234,
        ], $additionalOptions);

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn($newVersion);
        $this->environmentTypeMock->expects($this->once())
            ->method('get')
            ->willReturn($environmentType);
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

    /**
     * @param bool $newVersion
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
     * @param bool $newVersion
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
