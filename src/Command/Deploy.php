<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Magento\MagentoCloud\Scenario\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;

/**
 * CLI command for deploy hook. Responsible for installing/updating/configuring Magento
 */
class Deploy extends Command
{
    const NAME = 'deploy';

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param Processor $processor
     * @param FlagManager $flagManager
     */
    public function __construct(Processor $processor, FlagManager $flagManager)
    {
        $this->processor = $processor;
        $this->flagManager = $flagManager;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Deploys application');

        parent::configure();
    }

    /**
     * Deploy application: copy writable directories back, install or update Magento data.
     *
     * {@inheritdoc}
     *
     * @throws ProcessorException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->processor->execute([
                'scenario/deploy.xml'
            ]);
        } catch (ProcessorException $e) {
            $this->flagManager->set(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED);
            throw $e;
        }
    }
}
