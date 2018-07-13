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
use Symfony\Component\Console\Input\InputOption;
use Magento\MagentoCloud\Patch\ApplierFactory;

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
     * @var ApplierFactory
     */
    private $applierFactory;

    /**
     * @param LoggerInterface $logger
     * @param Manager $manager
     * @param ApplierFactory $applierFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Manager $manager,
        ApplierFactory $applierFactory
    ) {
        $this->logger = $logger;
        $this->manager = $manager;
        $this->applierFactory = $applierFactory;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Unapplies all patches.');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            "Force unapplying patches even if some don't seem to be applied."
        );
        if (!$this->applierFactory->create()->supportsUnapplyAllPatches()) {
            $this->setHidden(true);
        }
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->info('Unpatching started.');
            $this->manager->unapplyAll($input->getOption('force'));
            $this->logger->info('Unpatching finished.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }
}
