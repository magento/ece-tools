<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Validator\Deploy\PostDeploy;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\ValidatorFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnDeploy;

/**
 * @inheritdoc
 */
class IdealState extends Command
{
    const NAME = 'wizard:ideal-state';

    /**
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
     * @var ValidatorFactory
     */
    private $validatorFactory;

    /**
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @param OutputFormatter $outputFormatter
     * @param ValidatorFactory $validatorFactory
     * @param GlobalSection $globalConfig
     */
    public function __construct(
        OutputFormatter $outputFormatter,
        ValidatorFactory $validatorFactory,
        GlobalSection $globalConfig
    ) {
        $this->outputFormatter = $outputFormatter;
        $this->validatorFactory = $validatorFactory;
        $this->globalConfig = $globalConfig;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Verifies ideal state of configuration');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $errors = [];

        if ($this->validatorFactory->create(ScdOnDeploy::class)->validate() instanceof Success) {
            $errors[] = 'The SCD is not set for on-demand or the build stage';
        }

        if (!$this->validatorFactory->create(PostDeploy::class)->validate() instanceof Success) {
            $errors[] = 'Post-deploy hook is not configured';
        }

        if (!$this->globalConfig->get(GlobalSection::VAR_SKIP_HTML_MINIFICATION)) {
            $errors[] = 'Skip HTML minification is disabled';
        }

        foreach ($errors as $error) {
            $this->outputFormatter->writeItem($output, $error);
        }

        $message = $errors
            ? 'The configured state is not ideal'
            : 'The configured state is ideal';

        $this->outputFormatter->writeResult($output, !$errors, $message);
    }
}
