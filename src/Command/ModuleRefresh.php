<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Config\Module;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Performs module
 */
class ModuleRefresh extends Command
{
    const NAME = 'module:refresh';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Module
     */
    private $config;

    /**
     * @param LoggerInterface $logger
     * @param Module $config
     */
    public function __construct(LoggerInterface $logger, Module $config)
    {
        $this->logger = $logger;
        $this->config = $config;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Refresh config to enable newly added modules');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->info('Refreshing modules started.');
            $this->config->refresh();
            $this->logger->info('Refreshing modules completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
