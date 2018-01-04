<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Symfony\Component\Console\Command\Command;
use Magento\MagentoCloud\Command\Backup\Restore;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Psr\Log\LoggerInterface;

/**
 * CLI command for restoring Magento configuration files from backup.
 */
class BackupRestore extends Command
{
    /**
     * @var Restore
     */
    private $restore;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Wrapper
     */
    private $wrapper;

    /**
     * Command name
     */
    const NAME = 'backup:restore';

    /**
     * @param Restore $restore
     * @param LoggerInterface $logger
     * @param Wrapper $wrapper
     */
    public function __construct(Restore $restore, LoggerInterface $logger, Wrapper $wrapper)
    {
        $this->restore = $restore;
        $this->logger = $logger;
        $this->wrapper = $wrapper;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Restore important configuration files. Run backup:list to show the list of backup files');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Overwrite existing files during restoring a backup'
        );
        $this->addOption(
            'file',
            null,
            InputOption::VALUE_OPTIONAL,
            'A specific file recovery path'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->wrapper->execute(function () use ($input, $output) {
            $restore = true;
            if ($input->getOption('force')) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Command ' . self::NAME
                    . ' with option --force will rewrite your existed files. Do you want to continue [y/N]?',
                    false
                );
                $restore = $helper->ask($input, $output, $question);
            }

            if ($restore) {
                $this->restore->run($input, $output);
            }
        }, $output);
    }
}
