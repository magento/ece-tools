<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Validates configuration with given validators.
 */
class ValidateConfiguration implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $validators;

    /**
     * @param LoggerInterface $logger
     * @param array $validators
     */
    public function __construct(
        LoggerInterface $logger,
        array $validators
    ) {
        $this->logger = $logger;
        $this->validators = $validators;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->notice('Validating configuration');

        $messages = $this->collectMessages();

        ksort($messages);
        foreach ($messages as $level => $levelMessages) {
            $error = 'Fix configuration with given suggestions:' . PHP_EOL . implode(PHP_EOL, $levelMessages);

            if ($level >= ValidatorInterface::LEVEL_CRITICAL) {
                throw new ProcessException(
                    $error
                );
            }

            $this->logger->log($level, $error);
        }

        $this->logger->notice('End of validation');
    }

    /**
     * Returns all validation messages grouped by validation level
     *
     * @return array
     */
    private function collectMessages(): array
    {
        $messages = [];

        /* @var $validators ValidatorInterface[] */
        foreach ($this->validators as $level => $validators) {
            foreach ($validators as $validator) {
                $result = $validator->validate();

                if ($result instanceof Error) {
                    $messages[$level][] = '- ' . $result->getError();
                    if ($suggestion = $result->getSuggestion()) {
                        $messages[$level][] = implode(PHP_EOL, array_map(
                            function ($line) {
                                return '  ' . $line;
                            },
                            explode(PHP_EOL, $suggestion)
                        ));
                    }
                }
            }
        }

        return $messages;
    }
}
