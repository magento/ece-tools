<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema\Validator;

use Magento\MagentoCloud\Config\Validator\ResultInterface;

/**
 * Validates the config value
 */
interface ValidatorInterface
{
    /**
     * Validates the config value
     *
     * @param string $key
     * @param mixed $value
     * @return ResultInterface
     */
    public function validate(string $key, $value): ResultInterface;
}
