<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\EceTool\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for getting store ulr by store id.
 *
 * @api
 * @since 101.0.0
 */
class ConfigShowStoreUrlCommand extends Command
{
    /**#@+
     * Names of input arguments or options.
     */
    const INPUT_ARGUMENT_STORE_ID = 'store-id';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        parent::__construct();
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     * @since 101.0.0
     */
    protected function configure()
    {
        $this->addArgument(
            self::INPUT_ARGUMENT_STORE_ID,
            InputArgument::OPTIONAL,
            'Store ID'
        );

        $this->setName('config:show:store-url')
            ->setDescription(
                'Shows store base url for given id.'
            );
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $storeId = $input->getArgument(self::INPUT_ARGUMENT_STORE_ID);
            /** @var Store $store */
            $store = $this->storeManager->getStore($storeId);

            $output->writeln($store->getBaseUrl());
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Cli::RETURN_FAILURE;
        }
    }
}
