<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento\Shared;

/**
 * Interface for reading Magento shared config file (/app/etc/config.php).
 */
interface ReaderInterface extends \Magento\MagentoCloud\Filesystem\Reader\ReaderInterface
{
}
