<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Patch\Manager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class UnapplyPatches extends Command
{
    const NAME = 'unpatch';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param LoggerInterface $logger
     * @param Manager $manager
     */
    public function __construct(
        LoggerInterface $logger,
        Manager $manager
    ) {
        $this->logger = $logger;
        $this->manager = $manager;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Unapplies all patches.');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->info('Unpatching started.');
            $this->manager->unapplyAll();
            $this->logger->info('Unpatching finished.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }
}
