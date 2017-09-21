<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\DeployStaticContent\GenerateFresh;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\PackageManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class GenerateFreshTest extends TestCase
{
    /**
     * @var GenerateFresh|\PHPUnit_Framework_MockObject_MockObject
     */
    private $process;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var PackageManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $packageManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new GenerateFresh(
            $this->shellMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->connectionMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->packageManagerMock
        );
    }

    public function testExecute()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/pub/static/deployed_version.txt');
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Enabling Maintenance mode'],
                ['Extracting locales'],
                ['Generating static content for locales: en_GB fr_FR'],
                ['Maintenance mode is disabled.']
            );
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with(
                'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' '
                . 'UNION SELECT interface_locale FROM admin_user'
            )->willReturn([['value' => 'en_GB']]);
        $this->environmentMock->expects($this->exactly(2))
            ->method('getAdminLocale')
            ->willReturn('fr_FR');
        $this->shellMock->method('execute')
            ->withConsecutive(
                ['php ./bin/magento maintenance:enable  -vvv '],
                ['php ./bin/magento setup:static-content:deploy -f   en_GB fr_FR  -vvv '],
                ['php ./bin/magento maintenance:disable  -vvv ']
            );
        $this->environmentMock->method('getVerbosityLevel')
            ->willReturn(' -vvv ');
        $this->packageManagerMock->method('hasMagentoVersion')
            ->with('2.2')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteEmptyLocales()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/pub/static/deployed_version.txt');
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Enabling Maintenance mode'],
                ['Extracting locales'],
                ['Generating static content for locales: fr_FR'],
                ['Maintenance mode is disabled.']
            );
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with(
                'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' '
                . 'UNION SELECT interface_locale FROM admin_user'
            )->willReturn([]);
        $this->environmentMock->expects($this->exactly(2))
            ->method('getAdminLocale')
            ->willReturn('fr_FR');
        $this->shellMock->method('execute')
            ->withConsecutive(
                ['php ./bin/magento maintenance:enable  -vvv '],
                ['php ./bin/magento setup:static-content:deploy -f   fr_FR  -vvv '],
                ['php ./bin/magento maintenance:disable  -vvv ']
            );
        $this->environmentMock->method('getVerbosityLevel')
            ->willReturn(' -vvv ');
        $this->packageManagerMock->method('hasMagentoVersion')
            ->with('2.2')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteExcludedThemes()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/pub/static/deployed_version.txt');
        $this->loggerMock->method('info')
            ->withConsecutive(
                ['Enabling Maintenance mode'],
                ['Extracting locales'],
                ['Generating static content for locales: en_GB fr_FR'],
                ['Maintenance mode is disabled.']
            );
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with(
                'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' '
                . 'UNION SELECT interface_locale FROM admin_user'
            )->willReturn([['value' => 'en_GB']]);
        $this->environmentMock->expects($this->exactly(2))
            ->method('getAdminLocale')
            ->willReturn('fr_FR');
        $this->shellMock->method('execute')
            ->withConsecutive(
                ['php ./bin/magento maintenance:enable  -vvv '],
                ['php ./bin/magento setup:static-content:deploy -f  --exclude-theme=en_GB en_GB fr_FR  -vvv '],
                ['php ./bin/magento maintenance:disable  -vvv ']
            );
        $this->environmentMock->method('getVerbosityLevel')
            ->willReturn(' -vvv ');
        $this->environmentMock->method('getStaticDeployExcludeThemes')
            ->willReturn('en_GB');
        $this->packageManagerMock->method('hasMagentoVersion')
            ->with('2.2')
            ->willReturn(true);

        $this->process->execute();
    }
}
