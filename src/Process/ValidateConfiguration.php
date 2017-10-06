<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

use Magento\MagentoCloud\Config\ValidatorInterface;
use Psr\Log\LoggerInterface;

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
     * @internal param $type
     */
    public function __construct(
        LoggerInterface $logger,
        array $validators
    ) {
        $this->logger = $logger;
        $this->validators = $validators;
    }

    /**
     * Executes the process.
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->info('Validate configuration');

        /* @var $validators ValidatorInterface[] */
        foreach ($this->validators as $level => $validators) {
            foreach ($validators as $validator) {
                $result = $validator->validate();

                if ($result->hasError()) {
                    $this->logger->log($level, $result->getError());

                    if (!empty($result->getSuggestion())) {
                        $this->logger->log($level, $result->getSuggestion());
                    }

                    if ($level === ValidatorInterface::LEVEL_CRITICAL) {
                        throw new \RuntimeException('Please fix configuration with given recommendations');
                    }
                }
            }
        }

        $this->logger->info('End of validation');
    }
}
