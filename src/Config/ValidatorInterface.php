<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

/**
 * Interface for validators which runs at the very beginning of build or deploy phase
 */
interface ValidatorInterface
{
    const LEVEL_WARNING = 'warning';
    const LEVEL_CRITICAL = 'critical';

    /**
     * @return Validator\Result
     */
    public function validate(): Validator\Result;
}
