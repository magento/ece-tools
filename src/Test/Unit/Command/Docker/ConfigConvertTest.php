<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Docker;

use Magento\MagentoCloud\Command\Docker\ConfigConvert;
use Magento\MagentoCloud\Docker\Config\Converter;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
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
     * @var Converter|MockObject
     */
    private $converterMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->systemListMock = $this->createMock(SystemList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->converterMock = $this->createMock(Converter::class);

        $this->command = new ConfigConvert(
            $this->systemListMock,
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

        $this->systemListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/docker/config.php', true],
                ['magento_root/docker/config.php', true],
                ['magento_root/docker/config.env', false]
            ]);
        $this->fileMock->expects($this->once())
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
                    'magento_root/docker/config.env',
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

        $this->systemListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/docker/config.php', false],
                ['magento_root/docker/config.php.dist', true],
                ['magento_root/docker/config.env', false],
            ]);
        $this->fileMock->expects($this->once())
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
                    'magento_root/docker/config.env',
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

        $this->systemListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/docker/config.php', false],
                ['magento_root/docker/config.php.dist', true],
                ['magento_root/docker/config.env', true],
            ]);
        $this->fileMock->expects($this->once())
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
                ]
            ]);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->withConsecutive(
                [
                    'magento_root/docker/config.env',
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
                ['magento_root/docker/config.env']
            );

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @expectedException  \Magento\MagentoCloud\Filesystem\FileSystemException
     * @expectedExceptionMessage Source file magento_root/docker/config.php.dist does not exists
     * @throws FileSystemException
     */
    public function testExecuteNoSource()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
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
