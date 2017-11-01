<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Build;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\DeployStaticContent;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContentTest extends TestCase
{
    /**
     * @var DeployStaticContent
     */
    private $process;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var Build|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buildConfigMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var FileList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileListMock;

    /**
     * @var ArrayManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayManagerMock;

    /**
     * @var ProcessInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->buildConfigMock = $this->getMockBuilder(Build::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileListMock = $this->getMockBuilder(FileList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);

        $this->fileListMock->method('getConfig')
            ->willReturn(__DIR__ . '/_files/app/etc/config.php');

        $this->process = new DeployStaticContent(
            $this->loggerMock,
            $this->buildConfigMock,
            $this->fileMock,
            $this->environmentMock,
            $this->fileListMock,
            $this->arrayManagerMock,
            $this->processMock
        );
    }

    public function testExecute()
    {
        $flattenConfig = [
            'scopes' => [
                'websites' => [],
                'stores' => [],
            ],
        ];

        $this->fileMock->method('isExists')
            ->with(__DIR__ . '/_files/app/etc/config.php')
            ->willReturn(true);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(Build::OPT_SKIP_SCD)
            ->willReturn(false);
        $this->arrayManagerMock->method('flatten')
            ->willReturn([
                'scopes' => [
                    'websites' => [],
                    'stores' => [],
                ],
            ]);
        $this->arrayManagerMock->expects($this->exactly(2))
            ->method('filter')
            ->willReturnMap([
                [$flattenConfig, 'scopes/websites', false, ['websites1']],
                [$flattenConfig, 'scopes/stores', false, ['store1']],
            ]);
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteWithoutStores()
    {
        $flattenConfig = [
            'scopes' => [
                'websites' => [],
                'stores' => [],
            ],
        ];

        $this->fileMock->method('isExists')
            ->with(__DIR__ . '/_files/app/etc/config.php')
            ->willReturn(true);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(Build::OPT_SKIP_SCD)
            ->willReturn(false);
        $this->arrayManagerMock->method('flatten')
            ->willReturn([
                'scopes' => [
                    'websites' => [],
                    'stores' => [],
                ],
            ]);
        $this->arrayManagerMock->expects($this->exactly(2))
            ->method('filter')
            ->withConsecutive(
                [$flattenConfig, 'scopes/websites', false],
                [$flattenConfig, 'scopes/stores', false]
            )->willReturn([]);
        $this->processMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteNoConfig()
    {
        $this->fileMock->method('isExists')
            ->with(__DIR__ . '/_files/app/etc/config.php')
            ->willReturn(false);
        $this->buildConfigMock->expects($this->never())
            ->method('get');
        $this->processMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteSkipBuildOption()
    {
        $this->fileMock->method('isExists')
            ->with(__DIR__ . '/_files/app/etc/config.php')
            ->willReturn(true);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(Build::OPT_SKIP_SCD)
            ->willReturn(true);
        $this->processMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
