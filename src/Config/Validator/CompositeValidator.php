<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * @inheritdoc
 */
interface CompositeValidator extends ValidatorInterface
{
    /**
     * @return Error[]
     */
    public function getErrors(): array;
}
