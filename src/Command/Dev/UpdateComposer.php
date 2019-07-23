<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Dev;

use Magento\MagentoCloud\Command\Dev\UpdateComposer\ClearModuleRequirements;
use Magento\MagentoCloud\Command\Dev\UpdateComposer\ComposerGenerator;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Shell\ShellInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update composer command for deployment from git.
 */
class UpdateComposer extends Command
{
    const NAME = 'dev:git:update-composer';

    /**
     * @var ComposerGenerator
     */
    private $composerGenerator;

    /**
     * @var ShellInterface
     */
    private $shell;

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
     * @var ClearModuleRequirements
     */
    private $clearModuleRequirements;

    /**
     * @param ComposerGenerator $composerGenerator
     * @param ClearModuleRequirements $clearModuleRequirements
     * @param ShellInterface $shell
     * @param GlobalSection $globalSection
     * @param FileList $fileList
     * @param File $file
     */
    public function __construct(
        ComposerGenerator $composerGenerator,
        ClearModuleRequirements $clearModuleRequirements,
        ShellInterface $shell,
        GlobalSection $globalSection,
        FileList $fileList,
        File $file
    ) {
        $this->composerGenerator = $composerGenerator;
        $this->clearModuleRequirements = $clearModuleRequirements;
        $this->shell = $shell;
        $this->globalSection = $globalSection;
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
            ->setDescription('Updates composer for deployment from git.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gitOptions = $this->globalSection->get(GlobalSection::VAR_DEPLOY_FROM_GIT_OPTIONS);

        $scripts = $this->composerGenerator->getInstallFromGitScripts($gitOptions['repositories']);
        foreach (array_slice($scripts, 1) as $script) {
            $this->shell->execute($script);
        }

        $composer = $this->composerGenerator->generate($gitOptions['repositories']);

        if (!empty($gitOptions['clear_magento_module_requirements'])) {
            $this->clearModuleRequirements->generate($gitOptions['repositories']);
            $composer['scripts']['install-from-git'][] = 'php ' . ClearModuleRequirements::SCRIPT_PATH;
        }

        $this->file->filePutContents(
            $this->fileList->getMagentoComposer(),
            json_encode($composer, JSON_PRETTY_PRINT)
        );

        $output->writeln('Run composer update');
        $this->shell->execute('composer update --ansi --no-interaction');

        $output->writeln('Composer update finished.');
        $output->writeln('Please commit and push changed files.');
    }
}
