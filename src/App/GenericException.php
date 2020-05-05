<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App;

use Throwable;

/**
 * Base exception for general purposes.
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class GenericException extends \Exception
{
    /**
     * @inheritDoc
     */
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
