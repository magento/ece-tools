<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Command\Build\Generate;
use Magento\MagentoCloud\Command\Build\Transfer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

/**
 * CLI command for build hook. Responsible for preparing the codebase before it's moved to the server.
 *
 * @api
 */
class Build extends Command
{
    public const NAME = 'build';

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription('Builds application.');

        parent::configure();
    }

    /**
     * This method is a proxy for calling build:generate and build:transfer commands.
     *
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();

        if (null === $application) {
            throw new RuntimeException('Application is not defined');
        }

        $application->find(Generate::NAME)->execute($input, $output);
        $application->find(Transfer::NAME)->execute($input, $output);

        return Cli::SUCCESS;
    }
}
