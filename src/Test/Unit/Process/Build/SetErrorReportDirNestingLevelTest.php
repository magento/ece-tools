<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\SetErrorReportDirNestingLevel;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;


/**
 * @inheritdoc
 */
class SetErrorReportDirNestingLevelTest extends TestCase
{
    /**
     * @var SetErrorReportDirNestingLevel
     */
    private $processor;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->fileMock = $this->createMock(File::class);

        $this->processor = new SetErrorReportDirNestingLevel(
            $this->loggerMock,
            $this->configFileListMock,
            $this->stageConfigMock,
            $this->fileMock
        );
    }

    public function testExecutr()
    {

    }
}