<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Interface for different directory copying strategies.
 */
interface StrategyInterface
{
    const STRATEGY_COPY = 'copy';
    const STRATEGY_SYMLINK = 'symlink';

    /**
     * @param string $fromDirectory
     * @param string $toDirectory
     * @return bool
     * @throws FileSystemException
     */
    public function copy(string $fromDirectory, string $toDirectory): bool;
}
