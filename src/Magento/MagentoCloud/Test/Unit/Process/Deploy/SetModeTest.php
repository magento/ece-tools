<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\SetMode;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class SetModeTest extends TestCase
{
    /**
     * @var SetMode
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DeployConfig|Mock
     */
    private $deployConfigMock;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->deployConfigMock = $this->createMock(DeployConfig::class);

        $this->process = new SetMode(
            $this->environmentMock,
            $this->loggerMock,
            $this->shellMock,
            $this->fileMock,
            $this->deployConfigMock
        );
    }

    public function testExecute()
    {
        $mode = Environment::MAGENTO_PRODUCTION_MODE;
        $configFile = __DIR__ . '/_files/config.php';

        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn($mode);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->willReturn(sprintf("Set Magento application mode to '%s'", $mode));
        $this->deployConfigMock->expects($this->once())
            ->method('getConfigFilePath')
            ->willReturn($configFile);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                $configFile,
                "<?php\nreturn array (\n  'modules' => \n  array (\n  ),\n  'MAGE_MODE' => 'production',\n);"
            );

        $this->process->execute();
    }

    public function testExecuteDeveloperMode()
    {
        $mode = Environment::MAGENTO_DEVELOPER_MODE;
        $verbosity = ' -vvv ';

        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn($mode);
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn($verbosity);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->willReturn(sprintf("Set Magento application mode to '%s'", $mode));
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with(sprintf(
                "php ./bin/magento deploy:mode:set %s %s",
                $mode,
                $verbosity
            ));

        $this->process->execute();
    }
}
