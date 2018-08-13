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
     * @param $validators
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
     *
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->info('Validating configuration');

        $maxLevel = 0;
        $messages = [];

        /* @var $validators ValidatorInterface[] */
        foreach ($this->validators as $level => $validators) {
            foreach ($validators as $validator) {
                $result = $validator->validate();

                if ($result instanceof Error) {
                    $maxLevel = max($maxLevel, $level);

                    $messages[] = '- ' . $result->getError();
                    if ($result->getSuggestion()) {
                        $messages[] = implode(PHP_EOL, array_map(
                            function ($line) {
                                return '  ' . $line;
                            },
                            explode(PHP_EOL, $result->getSuggestion())
                        ));
                    }
                }
            }
        }

        $this->logger->info('End of validation');

        if (!$messages || !$maxLevel) {
            return;
        }

        $error = 'Fix configuration with given suggestions:' . PHP_EOL . implode(PHP_EOL, $messages);

        if ($maxLevel >= ValidatorInterface::LEVEL_CRITICAL) {
            throw new \RuntimeException(
                $error
            );
        }

        $this->logger->log($maxLevel, $error);
    }
}
