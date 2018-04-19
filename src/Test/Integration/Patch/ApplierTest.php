<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration\Patch;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Patch\GitApplier;
use Magento\MagentoCloud\Test\Integration\AbstractTest;
use Magento\MagentoCloud\Test\Integration\Bootstrap;

/**
 * @inheritdoc
 */
class ApplierTest extends AbstractTest
{
    /**
     * @var GitApplier
     */
    private $applier;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * Path to patch file
     *
     * @var string
     */
    private $patchFile;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::create();
        $application = $this->bootstrap->createApplication([]);

        $this->applier = $application->getContainer()
            ->get(GitApplier::class);
        $this->fileList = $application->getContainer()
            ->get(FileList::class);

        $this->patchFile = realpath(__DIR__ . '/../_files/patches/patch.diff');

        // Make sure our target file is in its original state
        $this->bootstrap->execute(sprintf(
            'cp -f %s %s',
            __DIR__ . '/../_files/patches/target_file.md',
            $this->bootstrap->getSandboxDir() . '/target_file.md'
        ));
    }

    protected function tearDown()
    {
        // Skip cleaning.
    }

    public function testApplyingPatch()
    {
        $this->applier->applyPatches([['name' => $this->patchFile]]);
        $content = $this->getTargetFileContents();
        $this->assertContains('# Hello Magento', $content);
        $this->assertContains('## Additional Info', $content);
    }

    public function testApplyingExistingPatch()
    {
        $this->bootstrap->execute(sprintf(
            'patch --directory=%s < %s',
            $this->bootstrap->getSandboxDir(),
            $this->patchFile
        ));

        $this->applier->applyPatches([['name' => $this->patchFile]]);

        $content = $this->getTargetFileContents();
        $this->assertContains('# Hello Magento', $content);
        $this->assertContains('## Additional Info', $content);

        $log = $this->getLogContent();
        $this->assertContains("NOTICE: Patch $this->patchFile was already applied.", $log);
    }

    /**
     * @return string
     */
    private function getTargetFileContents(): string
    {
        return file_get_contents($this->bootstrap->getSandboxDir() . '/target_file.md');
    }

    /**
     * @return string
     */
    private function getLogContent(): string
    {
        return file_get_contents($this->fileList->getCloudLog());
    }
}
