<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

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
    const NAME = 'patch';

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
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Applies custom patches');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ShellException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->manager->apply();
    }
}
