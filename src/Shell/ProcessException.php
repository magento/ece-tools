<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\App\GenericException;

/**
 * Exception for failed execution of CLI commands
 */
class ProcessException extends GenericException
{
}
