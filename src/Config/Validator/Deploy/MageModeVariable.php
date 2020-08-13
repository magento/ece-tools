<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\EnvironmentDataInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates value of MAGE_MODE variable.
 */
class MageModeVariable implements ValidatorInterface
{
    public const PRODUCTION_MODE = 'production';

    /**
     * @var EnvironmentDataInterface
     */
    private $envData;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param EnvironmentDataInterface $envData
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        EnvironmentDataInterface $envData,
        ResultFactory $resultFactory
    ) {
        $this->envData = $envData;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $mageMode = $this->envData->getMageMode();
        if (!$mageMode || $mageMode == self::PRODUCTION_MODE) {
            return $this->resultFactory->success();
        }

        return $this->resultFactory->error(
            sprintf(
                'Environment variable MAGE_MODE was found and the value differs from "%s".',
                self::PRODUCTION_MODE
            ),
            sprintf(
                'Magento Cloud does not support Magento modes other than "%1$s". '
                . 'Remove this variable, or change the value to "%1$s", the only supported mode on Cloud projects.',
                self::PRODUCTION_MODE
            ),
            Error::WARN_NOT_SUPPORTED_MAGE_MODE
        );
    }
}
