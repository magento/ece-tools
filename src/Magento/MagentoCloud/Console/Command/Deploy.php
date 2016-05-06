<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\MagentoCloud;

/**
 * Deploy an instance of Magento on the Magento Cloud
 */
class Deploy extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento-cloud:deploy')
            ->setDescription('Deploy an instance of Magento on the Magento Cloud');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $magento = new MagentoCloud($output);
        $magento->deploy();
    }
}
