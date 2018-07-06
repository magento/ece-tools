<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration\FileSystem;

use Magento\MagentoCloud\Test\Integration\AbstractTest;
use Magento\MagentoCloud\Test\Integration\Bootstrap;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Util\ForkManager\SingletonFactory as ForkManagerSingletonFactory;

/**
 * @inheritdoc
 */
class DriverTest extends AbstractTest
{
    /**
     * @var File
     */
    private $fileDriver;

    /**
     * Path to patch file
     *
     * @var ForkManagerSingletonFactory
     */
    private $forkManagerSingletonFactory;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::getInstance();
        $application = $this->bootstrap->createApplication([]);
        $this->fileDriver = $application->getContainer()->get(File::class);
        $this->forkManagerSingletonFactory = $application->getContainer()->get(ForkManagerSingletonFactory::class);
        $this->directoryList = $application->getContainer()->get(DirectoryList::class);
    }

    protected function tearDown()
    {
        // Skip cleaning.
    }

    public function testBackgroundClearDirectory()
    {
        $initPubStatic = $this->directoryList->getPath(DirectoryList::DIR_INIT) . '/pub/static';
        $initPubStaticSubdirectory = $initPubStatic . "/testdirectory";
        $this->assertDirectoryNotExists($initPubStaticSubdirectory);
        $this->assertTrue($this->fileDriver->createDirectory($initPubStaticSubdirectory));
        $this->assertDirectoryExists($initPubStaticSubdirectory);
        $this->fileDriver->backgroundClearDirectory($initPubStaticSubdirectory);
        $forkmanager = $this->forkManagerSingletonFactory->create();
        $forkmanager->waitForChildren();
        $this->assertDirectoryNotExists($initPubStaticSubdirectory);
    }
}
