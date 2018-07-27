<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

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

        if (!$this->magentoVersion->isGreaterOrEqual('2.2')
            && !empty($this->stageConfig->get(StageConfigInterface::VAR_SCD_STRATEGY))
        ) {
                $errors[] = sprintf(
                    '%s is available for Magento 2.2.0 and later.',
                    StageConfigInterface::VAR_SCD_STRATEGY
                );
        }

        if ($errors) {
            return $this->resultFactory->error(
                'The current configuration is not compatible with this version of Magento',
                implode(PHP_EOL, $errors)
            );
        }

        return $this->resultFactory->success();
    }
}
