<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Validates configuration with given validators.
 */
class ValidateConfiguration implements StepInterface
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
    public function execute(): void
    {
        $this->logger->notice('Validating configuration');

        $errors = $this->collectErrors();

        ksort($errors);
        /** @var Error[] $levelErrors */
        foreach ($errors as $level => $levelErrors) {
            $message = $this->createErrorMessage($levelErrors);

            if ($level >= ValidatorInterface::LEVEL_CRITICAL) {
                throw new StepException(
                    $message,
                    array_values($levelErrors)[0]->getErrorCode()
                );
            }

            $this->logger->log($level, $message);
        }

        $this->logger->notice('End of validation');
    }

    /**
     * Returns all validation messages grouped by validation level.
     * Converts validation level to integer value.
     *
     * @return array
     *
     * @throws StepException
     * @see Logger::toMonologLevel()
     */
    private function collectErrors(): array
    {
        $errors = [];

        /* @var $validators ValidatorInterface[] */
        foreach ($this->validators as $level => $validators) {
            $level = Logger::toMonologLevel($level);
            foreach ($validators as $name => $validator) {
                if (!$validator instanceof ValidatorInterface) {
                    $this->logger->info(sprintf('Validator "%s" was skipped', $name));
                    continue;
                }

                try {
                    $result = $validator->validate();
                } catch (ValidatorException $exception) {
                    throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
                }

                if ($result instanceof Error) {
                    $errors[$level][] = $result;
                }
            }
        }

        return $errors;
    }

    /**
     * Convert array of Errors to string message
     *
     * @param array $errors
     * @return string
     */
    private function createErrorMessage(array $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = '- ' . $error->getError();
            if ($suggestion = $error->getSuggestion()) {
                $messages[] = implode(PHP_EOL, array_map(
                    static function ($line) {
                        return '  ' . $line;
                    },
                    explode(PHP_EOL, $suggestion)
                ));
            }
        }

        return 'Fix configuration with given suggestions:' . PHP_EOL . implode(PHP_EOL, $messages);
    }
}
