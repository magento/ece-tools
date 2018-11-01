<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Docker;

use Magento\MagentoCloud\Command\Docker\ConfigConvert;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\SystemList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ConfigConvertTest extends TestCase
{
    /**
     * @var ConfigConvert
     */
    private $command;

    /**
     * @var SystemList|MockObject
     */
    private $systemListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->systemListMock = $this->createMock(SystemList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->command = new ConfigConvert(
            $this->systemListMock,
            $this->fileMock
        );
    }

    public function testExecute()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->systemListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(6))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/docker/config.php', true],
                ['magento_root/docker/config.php', true],
                ['magento_root/docker/config.env', false],
                ['magento_root/docker/global.php', true],
                ['magento_root/docker/global.php', true],
                ['magento_root/docker/global.env', false],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('requireFile')
            ->willReturnMap([
                [
                    'magento_root/docker/config.php',
                    [
                        'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                            [
                                'ADMIN_EMAIL' => 'test2@email.com',
                                'ADMIN_USERNAME' => 'admin2',
                                'SCD_COMPRESSION_LEVEL' => '0',
                                'MIN_LOGGING_LEVEL' => 'debug',
                            ]
                        )),
                    ],
                ],
                [

                    'magento_root/docker/global.php',
                    [
                        'MAGENTO_RUN_MODE' => 'production',
                    ],
                ],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('filePutContents')
            ->willReturnMap([
                [
                    'magento_root/docker/config.env',
                    $this->contains('MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJT'),
                ],
                [
                    'magento_root/docker/global.env',
                    'MAGENTO_RUN_MODE=InByb2R1Y3Rpb24i',
                ],
            ]);

        $this->command->execute($inputMock, $outputMock);
    }

    public function testExecuteUsingDist()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->systemListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(6))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/docker/config.php', false],
                ['magento_root/docker/config.php.dist', true],
                ['magento_root/docker/config.env', false],
                ['magento_root/docker/global.php', false],
                ['magento_root/docker/global.php.dist', true],
                ['magento_root/docker/global.env', false],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('requireFile')
            ->willReturnMap([
                [
                    'magento_root/docker/config.php.dist',
                    [
                        'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                            [
                                'ADMIN_EMAIL' => 'test2@email.com',
                                'ADMIN_USERNAME' => 'admin2',
                                'SCD_COMPRESSION_LEVEL' => '0',
                                'MIN_LOGGING_LEVEL' => 'debug',
                            ]
                        )),
                    ],
                ],
                [

                    'magento_root/docker/global.php.dist',
                    [
                        'MAGENTO_RUN_MODE' => 'production',
                    ],
                ],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('filePutContents')
            ->willReturnMap([
                [
                    'magento_root/docker/config.env',
                    $this->contains('MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJT'),
                ],
                [
                    'magento_root/docker/global.env',
                    'MAGENTO_RUN_MODE=InByb2R1Y3Rpb24i',
                ],
            ]);

        $this->command->execute($inputMock, $outputMock);
    }

    public function testExecuteUsingDistWithClean()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->systemListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(6))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/docker/config.php', false],
                ['magento_root/docker/config.php.dist', true],
                ['magento_root/docker/config.env', true],
                ['magento_root/docker/global.php', false],
                ['magento_root/docker/global.php.dist', true],
                ['magento_root/docker/global.env', true],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('requireFile')
            ->willReturnMap([
                [
                    'magento_root/docker/config.php.dist',
                    [
                        'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                            [
                                'ADMIN_EMAIL' => 'test2@email.com',
                                'ADMIN_USERNAME' => 'admin2',
                                'SCD_COMPRESSION_LEVEL' => '0',
                                'MIN_LOGGING_LEVEL' => 'debug',
                            ]
                        )),
                    ],
                ],
                [

                    'magento_root/docker/global.php.dist',
                    [
                        'MAGENTO_RUN_MODE' => 'production',
                    ],
                ],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('filePutContents')
            ->willReturnMap([
                [
                    'magento_root/docker/config.env',
                    $this->contains('MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJT'),
                ],
                [
                    'magento_root/docker/global.env',
                    'MAGENTO_RUN_MODE=InByb2R1Y3Rpb24i',
                ],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('deleteFile')
            ->withConsecutive(
                ['magento_root/docker/config.env'],
                ['magento_root/docker/global.env']
            );

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @expectedException  \Magento\MagentoCloud\Filesystem\FileSystemException
     * @expectedExceptionMessage Source file magento_root/docker/config.php.dist does not exists
     */
    public function testExecuteNoSource()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->systemListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/docker/config.php', false],
                ['magento_root/docker/config.php.dist', false],
            ]);

        $this->command->execute($inputMock, $outputMock);
    }
}
