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
    public const VAR_ERROR_REPORT_DIR_NESTING_LEVEL = 'ERROR_REPORT_DIR_NESTING_LEVEL';

    /**
     * Perform Baler JS bundling
     */
    public const VAR_SCD_USE_BALER = 'SCD_USE_BALER';

    /**
     * Magento quality patches list
     */
    public const VAR_QUALITY_PATCHES = 'QUALITY_PATCHES';

    /**
     * Skip composer dump-autoload
     */
    public const VAR_SKIP_COMPOSER_DUMP_AUTOLOAD = 'SKIP_COMPOSER_DUMP_AUTOLOAD';
}
