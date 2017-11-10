<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Creates instance of CopierInterface
 */
class StrategyFactory
{
    /**
     * @var File
     */
    private $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * @param string $strategy
     * @return StrategyInterface
     * @throws \RuntimeException If copier with given type not exists
     */
    public function create(string $strategy): StrategyInterface
    {
        switch ($strategy) {
            case StrategyInterface::STRATEGY_COPY:
                $strategyInstance = new CopyStrategy($this->file);
                break;
            case StrategyInterface::STRATEGY_SYMLINK:
                $strategyInstance = new SymlinkStrategy($this->file);
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Strategy "%s" not exists', $strategy)
                );
        }

        return $strategyInstance;
    }
}
