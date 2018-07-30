<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies configuration to be properly set and ready to use SCD on demand.
 */
class ScdOnDemand extends Command
{
    const NAME = 'wizard:scd-on-demand';

    /**
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
     * @var GlobalSection
     */
    private $globalStage;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param OutputFormatter $outputFormatter
     * @param GlobalSection $globalStage
     * @param Environment $environment
     */
    public function __construct(OutputFormatter $outputFormatter, GlobalSection $globalStage, Environment $environment)
    {
        $this->outputFormatter = $outputFormatter;
        $this->globalStage = $globalStage;
        $this->environment = $environment;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Verifies SCD on demand configuration');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $globalScdOnDemandEnabled = $this->globalStage->get(GlobalSection::VAR_SCD_ON_DEMAND);
        $globalScdOnDemandStatus = $globalScdOnDemandEnabled ? Environment::VAL_ENABLED : Environment::VAL_DISABLED;

        $scdOnDemandStatus = $this->environment->getVariable(
            GlobalSection::VAR_SCD_ON_DEMAND,
            $globalScdOnDemandStatus
        );
        $scdOnDemandEnabled = $scdOnDemandStatus == Environment::VAL_ENABLED;

        $this->outputFormatter->writeResult($output, $scdOnDemandEnabled, 'SCD on demand is ' . $scdOnDemandStatus);

        return (int)!$scdOnDemandEnabled;
    }
}
