<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Docker;

use Magento\MagentoCloud\Command\Docker\ConfigConvert;
use Magento\MagentoCloud\Docker\Config\Converter;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
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
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var Converter|MockObject
     */
    private $converterMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->converterMock = $this->createMock(Converter::class);

        $this->command = new ConfigConvert(
            $this->directoryListMock,
            $this->fileMock,
            $this->converterMock
        );
    }

    /**
     * @throws FileSystemException
     */
    public function testExecute()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->willReturnMap([
                ['docker_root/config.php', true],
                ['docker_root/config.php', true],
                ['docker_root/config.env', false]
            ]);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->willReturnMap([
                [
                    'docker_root/config.php',
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
                ]
            ]);
        $this->converterMock->expects($this->once())
            ->method('convert')
            ->willReturn([
                'MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJTCI6InRlc3Qy'
            ]);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->withConsecutive(
                [
                    'docker_root/config.env',
                    $this->stringStartsWith('MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJTCI6InRlc3Qy'),
                ]
            );

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @throws FileSystemException
     */
    public function testExecuteUsingDist()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->willReturnMap([
                ['docker_root/config.php', false],
                ['docker_root/config.php.dist', true],
                ['docker_root/config.env', false],
            ]);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->willReturnMap([
                [
                    'docker_root/config.php.dist',
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
            ]);
        $this->converterMock->expects($this->once())
            ->method('convert')
            ->willReturn([
                'MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJT'
            ]);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->withConsecutive(
                [
                    'docker_root/config.env',
                    $this->stringStartsWith('MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJT'),
                ]
            );

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @throws FileSystemException
     */
    public function testExecuteUsingDistWithClean()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->willReturnMap([
                ['docker_root/config.php', false],
                ['docker_root/config.php.dist', true],
                ['docker_root/config.env', true],
            ]);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->willReturnMap([
                [
                    'docker_root/config.php.dist',
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
                ]
            ]);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->withConsecutive(
                [
                    'docker_root/config.env',
                    $this->stringStartsWith('MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJT'),
                ]
            );
        $this->converterMock->expects($this->once())
            ->method('convert')
            ->willReturn([
                'MAGENTO_CLOUD_VARIABLES=eyJBRE1JTl9FTUFJTCI6InRlc3Qy'
            ]);
        $this->fileMock->expects($this->once())
            ->method('deleteFile')
            ->withConsecutive(
                ['docker_root/config.env']
            );

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @expectedException  \Magento\MagentoCloud\Filesystem\FileSystemException
     * @expectedExceptionMessage Source file docker_root/config.php.dist does not exists
     * @throws FileSystemException
     */
    public function testExecuteNoSource()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['docker_root/config.php', false],
                ['docker_root/config.php.dist', false],
            ]);

        $this->command->execute($inputMock, $outputMock);
    }
}
