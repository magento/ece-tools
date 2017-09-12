<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Build;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\DeployStaticContent;
use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

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
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var ArrayManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
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
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');

        $this->process = new DeployStaticContent(
            $this->shellMock,
            $this->loggerMock,
            $this->buildConfigMock,
            $this->fileMock,
            $this->environmentMock,
            $this->directoryListMock,
            $this->arrayManagerMock
        );
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
            ->with(Build::BUILD_OPT_SKIP_SCD)
            ->willReturn(false);
        $this->shellMock->expects($this->never())
            ->method('execute');
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
        $this->shellMock->expects($this->never())
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
        $this->shellMock->expects($this->never())
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
            ->with(Build::BUILD_OPT_SKIP_SCD)
            ->willReturn(true);
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
