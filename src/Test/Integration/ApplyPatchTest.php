<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Patch\Applier;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ApplyPatchTest extends TestCase
{
    private $bootstrap;

    /**
     * @var Applier
     */
    private $applier;

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
            ->get(Applier::class);
        $this->fileList = $application->getContainer()
            ->get(FileList::class);

        $this->patchFile  = realpath(__DIR__ . '/_files/patches/patch.diff');

        // Make sure our target file is in its original state
        $this->bootstrap->execute(sprintf(
            'cp -f %s %s',
            __DIR__ . '/_files/patches/target_file.md',
            $this->bootstrap->getSandboxDir() . '/target_file.md'
        ));
    }

    public function testApplyingPatch()
    {
        $this->applier->apply($this->patchFile);
        $content = $this->getTargetFileContents();
        $this->assertContains('# Hello Magento', $content);
        $this->assertContains('## Additional Info', $content);
    }

    public function testApplyingExistingPatch()
    {
        $this->bootstrap->execute(sprintf(
            'git apply -p0 --unsafe-paths --directory=%s %s',
            $this->bootstrap->getSandboxDir(),
            $this->patchFile
        ));

        $this->applier->apply($this->patchFile);

        $content = $this->getTargetFileContents();
        $this->assertContains('# Hello Magento', $content);
        $this->assertContains('## Additional Info', $content);

        $log = $this->getLogContent();
        $this->assertContains("NOTICE: Patch $this->patchFile was already applied.", $log);
    }

    private function getTargetFileContents()
    {
        return file_get_contents($this->bootstrap->getSandboxDir() . '/target_file.md');
    }

    private function getLogContent()
    {
        return file_get_contents($this->fileList->getCloudLog());
    }
}
