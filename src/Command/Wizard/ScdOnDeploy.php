<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Symfony\Component\Console\Command\Command;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnDeploy as ScdOnDeployValidator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies configuration to be properly set and ready to use SCD on deploy phase.
 */
class ScdOnDeploy extends Command
{
    const NAME = 'wizard:scd-on-deploy';

    /**
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
     * @var ScdOnDeployValidator
     */
    private $scdOnDeployValidator;

    /**
     * @param OutputFormatter $outputFormatter
     * @param ScdOnDeployValidator $scdOnDeployValidator
     */
    public function __construct(OutputFormatter $outputFormatter, ScdOnDeployValidator $scdOnDeployValidator)
    {
        $this->outputFormatter = $outputFormatter;
        $this->scdOnDeployValidator = $scdOnDeployValidator;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Verifies SCD on deploy configuration');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $errors = $this->scdOnDeployValidator->getErrors();
        $status = !$errors;

        /** @var Error $error */
        foreach ($errors as $error) {
            $this->outputFormatter->writeItem($output, $error->getError());
        }

        $this->outputFormatter->writeResult($output, $status, 'SCD on deploy is ' . ($status ? 'enabled' : 'disabled'));

        return (int)!$status;
    }
}
