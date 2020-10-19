<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Magento\MagentoCloud\Scenario\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for deploy hook. Responsible for installing/updating/configuring Magento
 *
 * @api
 */
class Deploy extends Command
{
    const NAME = 'deploy';

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var Manager
     */
    private $flagManager;

    /**
     * @param Processor $processor
     * @param Manager $flagManager
     */
    public function __construct(Processor $processor, Manager $flagManager)
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
            ->setDescription('Deploys the application.');

        parent::configure();
    }

    /**
     * Deploy application: copy writable directories back, install or update Magento data.
     *
     * {@inheritdoc}
     *
     * @throws ProcessorException
     * @throws ConfigurationMismatchException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->processor->execute([
                'scenario/deploy.xml'
            ]);
        } catch (ProcessorException $e) {
            $this->flagManager->set(Manager::FLAG_DEPLOY_HOOK_IS_FAILED);
            throw $e;
        }
    }
}
