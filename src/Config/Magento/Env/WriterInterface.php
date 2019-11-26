<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento\Env;

/**
 * Interface for writing configuration into Magento env file.
 *
 * @api
 */
interface WriterInterface extends \Magento\MagentoCloud\Filesystem\Writer\WriterInterface
{
}
