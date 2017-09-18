<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;
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
     * @var DeployConfig|Mock
     */
    private $deployCongigMock;

    /**
     * @var SearchEngine
     */
    private $process;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->deployCongigMock = $this->createMock(DeployConfig::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->process = new SearchEngine(
            $this->environmentMock,
            $this->loggerMock,
            $this->deployCongigMock
        );
    }

    public function testExecute()
    {
        $this->process->execute();
    }
}
