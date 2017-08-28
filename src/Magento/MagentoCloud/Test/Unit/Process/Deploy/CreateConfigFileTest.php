<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Deploy\CreateConfigFile;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;

class CreateConfigFileTest extends TestCase
{
    /**
     * @var CreateConfigFile
     */
    private $process;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DeployConfig|Mock
     */
    private $deployConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->deployConfigMock = $this->createMock(DeployConfig::class);

        $this->process = new CreateConfigFile(
            $this->deployConfigMock,
            $this->fileMock
        );

        parent::setUp();
    }

    public function testConfigFileNotExists()
    {
        $configFile = 'path/to/non-exists-file';

        $this->deployConfigMock->expects($this->once())
            ->method('getConfigFilePath')
            ->willReturn($configFile);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configFile)
            ->willReturn(false);

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($configFile, '<?php' . "\n" . 'return array();');

        $this->process->execute();
    }

    public function testConfigFileExists()
    {
        $configFile = 'path/to/exists-file';

        $this->deployConfigMock->expects($this->once())
            ->method('getConfigFilePath')
            ->willReturn($configFile);

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configFile)
            ->willReturn(true);

        $this->fileMock->expects($this->never())
            ->method('filePutContents');

        $this->process->execute();
    }
}
