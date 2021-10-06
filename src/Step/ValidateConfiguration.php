<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step;

use Magento\MagentoCloud\App\Error as AppError;
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

        if (!empty($errors)) {
            ksort($errors);

            $this->logger->notice('Fix configuration with given suggestions:');

            /** @var Error[] $levelErrors */
            foreach ($errors as $level => $levelErrors) {
                foreach ($levelErrors as $error) {
                    $this->logger->log($level, $error->getError(), [
                        'errorCode' => $error->getErrorCode(),
                        'suggestion' => $error->getSuggestion()
                    ]);
                }

                if ($level >= ValidatorInterface::LEVEL_CRITICAL) {
                    $error = array_values($levelErrors)[0];
                    throw new StepException($error->getError(), $error->getErrorCode() ?: AppError::DEFAULT_ERROR);
                }
            }
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
            /** @phpstan-ignore-next-line */
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
}
