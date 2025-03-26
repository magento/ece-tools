<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\App\GenericException;

/**
 * Exception when PDO connection could not be instantiated.
 */
class PDOException extends GenericException
{
}
