<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\DbConnection;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class DbConnectionTest extends TestCase
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
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var DbConnection
     */
    private $process;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->setMethods(['getRelationships'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->process = new DbConnection(
            $this->environmentMock,
            $this->configWriterMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([
                'database' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => '3306',
                        'path' => 'magento',
                        'username' => 'user',
                        'password' => 'password'
                    ]
                ],
            ]);

        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with([
                'db' => [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password'
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password'

                        ]
                    ]
                ]
            ]);

        $this->process->execute();
    }
}
