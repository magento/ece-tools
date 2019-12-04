<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Magento\Shared\Resolver;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Package\UndefinedPackageException;
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
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var ArrayManager|MockObject
     */
    private $arrayManagerMock;

    /**
     * @var Resolver|MockObject
     */
    private $configResolverMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->configResolverMock = $this->createMock(Resolver::class);

        $this->configFileStructure = new ConfigFileStructure(
            $this->arrayManagerMock,
            $this->resultFactoryMock,
            $this->configResolverMock
        );
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testRun(): void
    {
        $this->configResolverMock->expects($this->once())
            ->method('getPath')
            ->willReturn('magento_root/app/etc/config.php');
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

    /**
     * @throws UndefinedPackageException
     */
    public function testRunScdConfigNotExists(): void
    {
        $this->configResolverMock->expects($this->once())
            ->method('getPath')
            ->willReturn('magento_root/app/etc/config.php');
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

    /**
     * @throws UndefinedPackageException
     */
    public function testRunScdConfigNotExistsVersion21x(): void
    {
        $this->configResolverMock->expects($this->once())
            ->method('getPath')
            ->willReturn('magento_root/app/etc/config.local.php');
        $this->arrayManagerMock->expects($this->once())
            ->method('flatten')
            ->with([])
            ->willReturn([]);
        $this->arrayManagerMock
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
