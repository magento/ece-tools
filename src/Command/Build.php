<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Command\Build\Generate;
use Magento\MagentoCloud\Command\Build\Transfer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for build hook. Responsible for preparing the codebase before it's moved to the server.
 */
class Build extends Command
{
    const NAME = 'build';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Builds application');

        parent::configure();
    }

    /**
     * This method is a proxy for calling build:generate and build:transfer commands.
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->find(Generate::NAME)->execute($input, $output);
        $this->getApplication()->find(Transfer::NAME)->execute($input, $output);
    }
}
