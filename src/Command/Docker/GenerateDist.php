<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates .dist files.
 *
 * @codeCoverageIgnore
 */
class GenerateDist extends Command
{
    const NAME = 'docker:generate-dist';

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param ShellInterface $shell
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setAliases(['docker:config:convert'])
            ->setDescription('(deprecated) Generates Docker .dist files');
    }

    /**
     * {@inheritDoc}
     *
     * @throws ShellException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $process = $this->shell->execute('./vendor/bin/ece-docker build:dist');

        $output->write($process->getOutput());

        return $process->getExitCode();
    }
}
