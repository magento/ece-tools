<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Dev;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 */
class ClearModuleRequirements extends Command
{
    const NAME = 'dev:git:clear-module-requirements';

    /**
     * @var GlobalSection
     */
    private $globalSection;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param GlobalSection $globalSection
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     * @param File $file
     */
    public function __construct(
        GlobalSection $globalSection,
        DirectoryList $directoryList,
        FileList $fileList,
        File $file
    ) {
        $this->globalSection = $globalSection;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;
        $this->file = $file;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Clears module requirements.');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gitOptions = $this->globalSection->get(GlobalSection::VAR_DEPLOY_FROM_GIT_OPTIONS);

        foreach (array_keys($gitOptions) as $repoName) {
            $baseRepoFolder = $this->directoryList->getMagentoRoot() . '/' . $repoName;
            foreach (glob($baseRepoFolder . '/app/code/Magento/*') as $moduleDir) {
                if (!$this->file->isExists($moduleDir . '/composer.json')) {
                    continue;
                }

                $composerJson = json_decode($this->file->fileGetContents($moduleDir . '/composer.json'), true);

                foreach ($composerJson['require'] as $requireName => $requireVersion) {
                    if (strpos($requireName, 'magento/') !== false) {
                        unset($composerJson['require'][$requireName]);
                    }
                }

                $this->file->filePutContents(
                    $moduleDir . '/composer.json',
                    json_encode($composerJson, JSON_PRETTY_PRINT)
                );
            }
        }
    }
}
