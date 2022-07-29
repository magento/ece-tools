<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Dev;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Command\Dev\UpdateComposer\ClearModuleRequirements;
use Magento\MagentoCloud\Command\Dev\UpdateComposer\ComposerGenerator;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update composer command for deployment from git.
 *
 * @api
 */
class UpdateComposer extends Command
{
    public const NAME = 'dev:git:update-composer';

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
    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription('Updates composer for deployment from git.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConfigException
     * @throws FileSystemException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $gitOptions = $this->globalSection->get(GlobalSection::VAR_DEPLOY_FROM_GIT_OPTIONS);

        $InstallFromGitScripts = $this->composerGenerator->getInstallFromGitScripts($gitOptions['repositories']);
        foreach (array_slice($InstallFromGitScripts, 1) as $script) {
            $this->shell->execute($script);
        }

        // Preparing framework modules for installation
        $frameworkPreparationScript = $this->composerGenerator->getFrameworkPreparationScript(
            array_keys($gitOptions['repositories'])
        );
        foreach ($frameworkPreparationScript as $script) {
            $this->shell->execute($script);
        }

        $composer = $this->composerGenerator->generate(
            $gitOptions['repositories'],
            array_merge($InstallFromGitScripts, $frameworkPreparationScript)
        );

        if (!empty($gitOptions['clear_magento_module_requirements'])) {
            $clearRequirementsScript = $this->clearModuleRequirements->generate();
            $composer['scripts']['install-from-git'][] = 'php ' . $clearRequirementsScript;
        }

        $this->file->filePutContents(
            $this->fileList->getMagentoComposer(),
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $output->writeln('Run composer update');
        $this->shell->execute('composer update --ansi --no-interaction');

        $output->writeln('Composer update finished.');
        $output->writeln('Please commit and push changed files.');

        return Cli::SUCCESS;
    }
}
