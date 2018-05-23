<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config as SearchEngineConfig;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
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
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var SearchEngineConfig|Mock
     */
    private $configMock;

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

        $this->process = new SearchEngine(
            $this->loggerMock,
            $this->envWriterMock,
            $this->sharedWriterMock,
            $this->magentoVersionMock,
            $this->configMock
        );
    }

    /**
     * @param bool $newVersion
     * @param InvokedCount $useShareWriter
     * @param InvokedCount $useEnvWriter
     * @dataProvider executeDataProvider()
     * @return void
     */
    public function testExecute(
        bool $newVersion,
        InvokedCount $useShareWriter,
        InvokedCount $useEnvWriter
    ) {
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
            ->method('isGreaterOrEqual')
            ->willReturn($newVersion);
        $this->sharedWriterMock->expects($useShareWriter)
            ->method('update')
            ->with($config);
        $this->envWriterMock->expects($useEnvWriter)
            ->method('update')
            ->with($config);

        $this->process->execute();
    }

    public function executeDataProvider(): array
    {
        return [
            [
                'newVersion' => true,
                'useShareWriter' => $this->never(),
                'useEnvWriter' => $this->once(),
            ],
            [
                'newVersion' => false,
                'useShareWriter' => $this->once(),
                'useEnvWriter' => $this->never(),
            ]
        ];
    }
}
