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
use Magento\MagentoCloud\Patch\ApplierFactory;

/**
 * @inheritdoc
 */
class ShowAppliedPatches extends Command
{
    const NAME = 'patch:show-applied';

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
            ->setDescription('Shows which patches are applied');
        if (!$this->applierFactory->create()->supportsShowAppliedPatches()) {
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
            $this->manager->showApplied();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }
}
