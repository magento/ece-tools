<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyInterface;

/**
 * Returns list of recoverable directories
 */
class RecoverableDirectoryList
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns list of recoverable directories with recover strategy types.
     *
     * @return array
     */
    public function getList(): array
    {
        $isSymlinkEnabled = $this->environment->isStaticContentSymlinkOn();

        return [
            [
                'directory' => 'var/view_preprocessed',
                'strategy' => $isSymlinkEnabled ?
                    StrategyInterface::STRATEGY_SYMLINK : StrategyInterface::STRATEGY_COPY
            ],
            [
                'directory' => 'app/etc',
                'strategy' => StrategyInterface::STRATEGY_COPY
            ],
            [
                'directory' => 'pub/media',
                'strategy' => StrategyInterface::STRATEGY_COPY
            ]
        ];
    }
}
