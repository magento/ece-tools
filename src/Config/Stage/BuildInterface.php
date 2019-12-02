<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * Provides access to configuration of build stage.
 *
 * @api
 */
interface BuildInterface extends StageConfigInterface
{
    /**
     * Subdirectory nesting level
     */
    const VAR_ERROR_REPORT_DIR_NESTING_LEVEL = 'ERROR_REPORT_DIR_NESTING_LEVEL';
}
