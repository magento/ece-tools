<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class DbDump for safely creating backup of database
 */
class DbDump extends Command
{
    const NAME = 'db:dump';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var Wrapper
     */
    private $wrapper;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param Wrapper $wrapper
     */
    public function __construct(ProcessInterface $process, LoggerInterface $logger, Wrapper $wrapper)
    {
        $this->process = $process;
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
            ->setDescription('Creates backup of database');

        parent::configure();
    }

    /**
     * Creates DB dump.
     * Command requires confirmation before execution.
     *
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->wrapper->execute(function () use ($input, $output) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'We suggest to enable maintenance mode before running this command. Do you want to continue [y/N]?',
                false
            );
            if (!$helper->ask($input, $output, $question) && $input->isInteractive()) {
                return null;
            }

            $this->logger->info('Starting backup.');
            $this->process->execute();
            $this->logger->info('Backup completed.');
        }, $output);
    }
}
