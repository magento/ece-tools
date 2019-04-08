<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config as SearchEngineConfig;
use Magento\MagentoCloud\Process\ProcessException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var EnvWriter|MockObject
     */
    private $envWriterMock;

    /**
     * @var SharedWriter|MockObject
     */
    private $sharedWriterMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var SearchEngineConfig|MockObject
     */
    private $configMock;

    /**
     * @var SearchEngine\ElasticSuite|MockObject
     */
    private $elasticSuiteMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->envWriterMock = $this->createMock(EnvWriter::class);
        $this->sharedWriterMock = $this->createMock(SharedWriter::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->configMock = $this->createMock(SearchEngineConfig::class);
        $this->elasticSuiteMock = $this->createMock(SearchEngine\ElasticSuite::class);

        $this->process = new SearchEngine(
            $this->loggerMock,
            $this->envWriterMock,
            $this->sharedWriterMock,
            $this->magentoVersionMock,
            $this->configMock,
            $this->elasticSuiteMock
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecute()
    {
        $searchConfig = ['engine' => 'mysql'];
        $config['system']['default']['catalog']['search'] = $searchConfig;

        $this->configMock->expects($this->once())
            ->method('get')
            ->willReturn($searchConfig);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql']
            );
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willReturn(false);
        $this->sharedWriterMock->expects($this->never())
            ->method('update')
            ->with($config);
        $this->envWriterMock->expects($this->once())
            ->method('update')
            ->with($config);

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     */
    public function testExecute21()
    {
        $searchConfig = ['engine' => 'mysql'];
        $config['system']['default']['catalog']['search'] = $searchConfig;

        $this->configMock->expects($this->once())
            ->method('get')
            ->willReturn($searchConfig);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql']
            );
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willReturn(true);
        $this->sharedWriterMock->expects($this->once())
            ->method('update')
            ->with($config);
        $this->envWriterMock->expects($this->never())
            ->method('update')
            ->with($config);

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteWithElasticSuite()
    {
        $searchConfig = ['engine' => 'mysql'];
        $config['system']['default']['catalog']['search'] = $searchConfig;
        $config['system']['default' ]['smile_elasticsuite_core_base_settings'] = ['elastic' => 'suite'];

        $this->configMock->expects($this->once())
            ->method('get')
            ->willReturn($searchConfig);
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql'],
                ['Configuring ElasticSuite']
            );
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willReturn(false);
        $this->sharedWriterMock->expects($this->never())
            ->method('update')
            ->with($config);
        $this->envWriterMock->expects($this->once())
            ->method('update')
            ->with($config);
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSuiteMock->expects($this->once())
            ->method('get')
            ->willReturn(['elastic' => 'suite']);

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'is21' => false,
                'useSharedWriter' => $this->never(),
                'useEnvWriter' => $this->once(),
            ],
            [
                'is21' => true,
                'useSharedWriter' => $this->once(),
                'useEnvWriter' => $this->never(),
            ],
        ];
    }

    /**
     * @throws ProcessException
     *
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     */
    public function testExecuteWithException()
    {
        $searchConfig = ['engine' => 'mysql'];
        $config['system']['default']['catalog']['search'] = $searchConfig;

        $this->configMock->expects($this->once())
            ->method('get')
            ->willReturn($searchConfig);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql']
            );
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willReturn(false);
        $this->sharedWriterMock->expects($this->never())
            ->method('update')
            ->with($config);
        $this->envWriterMock->expects($this->once())
            ->method('update')
            ->with($config)
            ->willThrowException(new FileSystemException('Some error'));

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     *
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     */
    public function testExecuteWithPackageException()
    {
        $searchConfig = ['engine' => 'mysql'];

        $this->configMock->expects($this->once())
            ->method('get')
            ->willReturn($searchConfig);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql']
            );
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willThrowException(new UndefinedPackageException('Some error'));

        $this->process->execute();
    }
}
