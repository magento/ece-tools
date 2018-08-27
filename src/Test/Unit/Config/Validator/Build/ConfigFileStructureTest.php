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
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var ArrayManager|MockObject
     */
    private $arrayManagerMock;

    /**
     * @var SharedConfig|MockObject
     */
    private $configResolverMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->configResolverMock = $this->createMock(SharedConfig::class);

        $this->configFileStructure = new ConfigFileStructure(
            $this->arrayManagerMock,
            $this->fileMock,
            $this->resultFactoryMock,
            $this->configResolverMock
        );
    }

    public function testRun()
    {

        $this->configResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn('magento_root/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->with('magento_root/app/etc/config.php')
            ->willReturn([
                'scopes' => [
                    'websites' => [
                        'key' => 'value',
                    ],
                ],
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
        $this->configResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn('magento_root/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
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
                    'suggestion' => 'To speed up the deploy process do the following:' . PHP_EOL
                        . '1. Using SSH, log in to your Magento Cloud account' . PHP_EOL
                        . '2. Run "php ./vendor/bin/ece-tools config:dump"' . PHP_EOL
                        . '3. Using SCP, copy the app/etc/config.php file to your local repository' . PHP_EOL
                        . '4. Add, commit, and push your changes to the app/etc/config.php file',
                ]
            )
            ->willReturn($resultMock);

        $result = $this->configFileStructure->validate();

        $this->assertInstanceOf(Result\Error::class, $result);
    }

    public function testRunScdConfigNotExistsVersion21x()
    {
        $this->configResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn('magento_root/app/etc/config.local.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->with('magento_root/app/etc/config.local.php')
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
                    'error' => 'No stores/website/locales found in config.local.php',
                    'suggestion' => 'To speed up the deploy process do the following:' . PHP_EOL
                        . '1. Using SSH, log in to your Magento Cloud account' . PHP_EOL
                        . '2. Run "php ./vendor/bin/ece-tools config:dump"' . PHP_EOL
                        . '3. Using SCP, copy the app/etc/config.local.php file to your local repository' . PHP_EOL
                        . '4. Add, commit, and push your changes to the app/etc/config.local.php file',
                ]
            )
            ->willReturn($resultMock);

        $result = $this->configFileStructure->validate();

        $this->assertInstanceOf(Result\Error::class, $result);
    }
}
