<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * @inheritdoc
 */
interface CompositeValidator extends ValidatorInterface
{
    /**
     * @return ResultInterface[]
     */
    public function validateAll(): array;
}
