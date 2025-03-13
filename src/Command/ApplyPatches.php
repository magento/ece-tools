<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Shell\ShellException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 *
 * @deprecated
 */
class ApplyPatches extends Command
{
    public const NAME = 'patch';

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Applies custom patches.');

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws ShellException
     * @throws ConfigException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->manager->apply();

        return Cli::SUCCESS;
    }
}
