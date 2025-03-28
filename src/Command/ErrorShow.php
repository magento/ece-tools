<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\App\Logger\Error\ReaderInterface;
use Magento\MagentoCloud\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Display info about particular error or info about all errors from the last deployment.
 *
 * @api
 */
class ErrorShow extends Command
{
    public const NAME = 'error:show';
    public const ARGUMENT_ERROR_CODE = 'error-code';
    public const OPTION_JSON_FORMAT = 'json';

    /**
     * @var ErrorInfo
     */
    private $errorInfo;

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @param ErrorInfo $errorInfo
     * @param ReaderInterface $reader
     */
    public function __construct(ErrorInfo $errorInfo, ReaderInterface $reader)
    {
        $this->errorInfo = $errorInfo;
        $this->reader = $reader;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Displays info about error by error id or info about all errors from the last deployment.')
            ->addArgument(
                self::ARGUMENT_ERROR_CODE,
                InputArgument::OPTIONAL,
                'Error code, if not passed command display info about all errors from the last deployment'
            )
            ->addOption(
                self::OPTION_JSON_FORMAT,
                'j',
                InputOption::VALUE_NONE,
                'Used for getting result in JSON format'
            );
    }

    /**
     * Display info about particular error or info about all errors from the last deployment
     *
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errorCode = (int)$input->getArgument(self::ARGUMENT_ERROR_CODE);
        if ($errorCode) {
            $errorInfo = $this->errorInfo->get($errorCode);
            if (empty($errorInfo)) {
                $output->writeln(sprintf('Error with code %s is not registered in the error schema', $errorCode));

                return Cli::FAILURE;
            }
            $errorInfo['errorCode'] = $errorCode;
            $errors = [$errorCode => $errorInfo];
        } else {
            $errors = $this->reader->read();
            if (empty($errors)) {
                $output->writeln('The error log is empty or does not exist');

                return Cli::FAILURE;
            }
        }

        if ($input->getOption(self::OPTION_JSON_FORMAT)) {
            $output->writeln(json_encode($errors));

            return Cli::SUCCESS;
        }

        $errorCount = count($errors);
        $i = 0;
        foreach ($errors as $errorInfo) {
            $i++;
            $output->write($this->formatMessage($errorInfo));
            if ($errorCount !== $i) {
                $output->writeln(str_repeat('-', 15) . PHP_EOL);
            }
        }

        return Cli::SUCCESS;
    }

    /**
     * @param array $errorInfo
     * @return string
     */
    private function formatMessage(array $errorInfo): string
    {
        ksort($errorInfo);

        $result = '';

        foreach ($errorInfo as $key => $value) {
            $result .= sprintf('%s: %s' . PHP_EOL, $key, $value);
        }

        return $result;
    }
}
