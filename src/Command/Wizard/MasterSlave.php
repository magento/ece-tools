<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class MasterSlave extends Command
{
    const NAME = 'wizard:master-slave';

    /**
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
     * @var DeployInterface
     */
    private $deployConfig;

    /**
     * @param OutputFormatter $outputFormatter
     * @param DeployInterface $deployConfig
     */
    public function __construct(OutputFormatter $outputFormatter, DeployInterface $deployConfig)
    {
        $this->outputFormatter = $outputFormatter;
        $this->deployConfig = $deployConfig;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Verifies master-slave configuration');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $errors = [];

        if (!$this->deployConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)) {
            $errors[] = 'MySQL slave connection is not configured';
        }

        if (!$this->deployConfig->get(DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION)) {
            $errors[] = 'Redis slave connection is not configured';
        }

        foreach ($errors as $error) {
            $this->outputFormatter->writeItem($output, $error);
        }

        $message = $errors
            ? 'Slave connections are not configured'
            : 'Slave connections are configured';

        $this->outputFormatter->writeResult($output, !$errors, $message);
    }
}
