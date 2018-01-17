<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ConfigFileStructureTest extends TestCase
{
    /**
     * @var ConfigFileStructure
     */
    private $configFileStructure;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @var ArrayManager|Mock
     */
    private $arrayManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);

        $this->configFileStructure = new ConfigFileStructure(
            $this->arrayManagerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->resultFactoryMock
        );
    }

    public function testRun()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->with('magento_root/app/etc/config.php')
            ->willReturn([
                'scopes' => [
                    'websites' => [
                        'key' => 'value'
                    ]
                ]
            ]);
        $this->arrayManagerMock->expects($this->once())
            ->method('flatten')
            ->willReturn(['scopes/websites/key' => 'value']);
        $this->arrayManagerMock->expects($this->exactly(2))
            ->method('filter')
            ->withConsecutive(
                [['scopes/websites/key' => 'value'], 'scopes/websites', false],
                [['scopes/websites/key' => 'value'], 'scopes/stores', false]
            )
            ->willReturnOnConsecutiveCalls(
                ['scopes/websites/key' => 'value'],
                []
            );
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Result\Success::class));

        $result = $this->configFileStructure->validate();

        $this->assertInstanceOf(Result\Success::class, $result);
    }

    public function testRunScdConfigNotExists()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->with('magento_root/app/etc/config.php')
            ->willReturn([]);
        $this->arrayManagerMock->expects($this->once())
            ->method('flatten')
            ->with([])
            ->willReturn([]);
        $this->arrayManagerMock->expects($this->any())
            ->method('filter')
            ->with([])
            ->willReturn([]);
        $resultMock = $this->createMock(Result\Error::class);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ResultInterface::ERROR,
                [
                    'error' => 'No stores/website/locales found in config.php',
                    'suggestion' => 'To speed up the deploy process, please run the following commands:' . PHP_EOL
                        . '1. php ./vendor/bin/ece-tools config:dump' . PHP_EOL
                        . '2. git add -f app/etc/config.php' . PHP_EOL
                        . '3. git commit -m \'Updating config.php\'' . PHP_EOL
                        . '4. git push'
                ]
            )
            ->willReturn($resultMock);

        $result = $this->configFileStructure->validate();

        $this->assertInstanceOf(Result\Error::class, $result);
    }
}
