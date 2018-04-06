<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild as ScdOnBuildValidator;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Util\OutputFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ScdOnBuild extends Command
{
    /**
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
     * @var ScdOnBuild
     */
    private $scdOnBuildValidator;

    /**
     * @param OutputFormatter $outputFormatter
     * @param ScdOnBuildValidator $scdOnBuildValidator
     */
    public function __construct(
        OutputFormatter $outputFormatter,
        ScdOnBuildValidator $scdOnBuildValidator
    ) {
        $this->outputFormatter = $outputFormatter;
        $this->scdOnBuildValidator = $scdOnBuildValidator;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('wizard:scd-on-build')
            ->setDescription('Verifies SCD on build phase configuration');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $results = $this->scdOnBuildValidator->validateAll();
        $status = !$this->scdOnBuildValidator->validate() instanceof Error;

        foreach ($results as $error) {
            if ($error instanceof Error) {
                $this->outputFormatter->writeItem($output, $error->getError());
            }
        }

        $this->outputFormatter->writeResult($output, $status, 'SCD on build is ' . ($status ? 'enabled' : 'disabled'));
    }
}
