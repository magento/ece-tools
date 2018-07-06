<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Docker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * Builds Docker configuration for Magento project.
 */
class Up extends Command
{
    const NAME = 'docker:up';

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Docker Up');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->shell->execute('docker-compose run cli_build magento-cloud-build');
        $this->shell->execute('docker-compose run cli_deploy magento-cloud-deploy');
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return !$this->environment->isMasterBranch();
    }
}
