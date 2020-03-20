<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Interface for different directory copying strategies.
 */
interface StrategyInterface
{
    public const STRATEGY_COPY = 'copy';
    public const STRATEGY_SYMLINK = 'symlink';
    public const STRATEGY_SUB_SYMLINK = 'sub_symlink';
    public const STRATEGY_COPY_SUB_FOLDERS = 'copy_sub_folders';

    /**
     * @param string $fromDirectory Origin directory
     * @param string $toDirectory Destination directory
     * @return bool True if copy process finished successfully, False if folders copying wasn't performed
     * @throws FileSystemException When happened filesystem related error
     */
    public function copy(string $fromDirectory, string $toDirectory): bool;
}
