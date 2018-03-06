<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Dev;

use Magento\MagentoCloud\Command\Dev\UpdateComposer\ComposerGenerator;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Shell\ShellInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
     * @param ComposerGenerator $composerGenerator
     * @param ShellInterface $shell
     * @param GlobalSection $globalSection
     * @param FileList $fileList
     * @param File $file
     */
    public function __construct(
        ComposerGenerator $composerGenerator,
        ShellInterface $shell,
        GlobalSection $globalSection,
        FileList $fileList,
        File $file
    ) {
        $this->composerGenerator = $composerGenerator;
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

        foreach (ComposerGenerator::POSSIBLE_REPOS as $repoName) {
            $this->addOption(
                $repoName . '-branch',
                null,
                $repoName === 'ce' ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL,
                sprintf('Name of %s branch, 2.2.3 for example', $repoName)
            );
            $this->addOption(
                $repoName . '-repo',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf('Url of %s git repository', $repoName)
            );
        }

        $this->addOption(
            'github-token',
            null,
            InputOption::VALUE_OPTIONAL,
            'Github token for cloning git branches.'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gitOptions = $this->globalSection->get(GlobalSection::VAR_DEPLOY_FROM_GIT_OPTIONS);

        foreach ($this->composerGenerator->getInstallFromGitScripts($gitOptions) as $script) {
            $this->shell->execute($script);
        }

        $this->file->filePutContents(
            $this->fileList->getMagentoComposer(),
            json_encode($this->composerGenerator->generate($gitOptions), JSON_PRETTY_PRINT)
        );

        echo "Run composer update\n";
        $this->shell->execute('composer update');
    }
}
