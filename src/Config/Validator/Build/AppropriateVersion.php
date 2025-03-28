<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Checks that configuration from build phase is appropriate for current magento version.
 */
class AppropriateVersion implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param MagentoVersion $magentoVersion
     * @param BuildInterface $stageConfig
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        MagentoVersion $magentoVersion,
        BuildInterface $stageConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->stageConfig = $stageConfig;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $errors = [];

        foreach ([StageConfigInterface::VAR_SCD_STRATEGY, StageConfigInterface::VAR_SCD_MAX_EXEC_TIME] as $variable) {
            if (!$this->magentoVersion->isGreaterOrEqual('2.2')
                && !empty($this->stageConfig->get($variable))
            ) {
                $errors[] = sprintf(
                    '%s is available for Magento 2.2.0 and later.',
                    $variable
                );
            }
        }

        if ($errors) {
            return $this->resultFactory->error(
                'The current configuration is not compatible with this version of Magento',
                implode(PHP_EOL, $errors),
                Error::WARN_CONFIGURATION_VERSION_MISMATCH
            );
        }

        return $this->resultFactory->success();
    }
}
