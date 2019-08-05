<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Validator\IdealState as IdealStateValidator;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var IdealStateValidator
     */
    private $validator;

    /**
     * @param OutputFormatter $outputFormatter
     * @param IdealStateValidator $validator
     */
    public function __construct(OutputFormatter $outputFormatter, IdealStateValidator $validator)
    {
        $this->outputFormatter = $outputFormatter;
        $this->validator = $validator;

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
        $message = 'The configured state is ideal';

        if (($result = $this->validator->validate()) instanceof Error) {
            $message = $result->getError();

            foreach ($this->validator->getErrors() as $error) {
                $this->outputFormatter->writeItem($output, $error);
            }
        }

        $this->outputFormatter->writeResult($output, $result instanceof Success, $message);

        return count($this->validator->getErrors());
    }
}
