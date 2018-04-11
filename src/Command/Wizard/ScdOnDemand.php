<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Config\GlobalSection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;

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
     * @param OutputFormatter $outputFormatter
     * @param GlobalSection $globalStage
     */
    public function __construct(OutputFormatter $outputFormatter, GlobalSection $globalStage)
    {
        $this->outputFormatter = $outputFormatter;
        $this->globalStage = $globalStage;

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
        $scdOnDemandEnabled = $this->globalStage->get(GlobalSection::VAR_SCD_ON_DEMAND);
        $scdOnDemandStatus = $scdOnDemandEnabled ? 'enabled' : 'disabled';

        $this->outputFormatter->writeResult($output, $scdOnDemandEnabled, 'SCD on demand is ' . $scdOnDemandStatus);

        return (int)!$scdOnDemandEnabled;
    }
}
