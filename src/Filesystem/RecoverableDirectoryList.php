<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;

/**
 * Returns list of recoverable directories
 */
class RecoverableDirectoryList
{
    const OPTION_DIRECTORY = 'directory';
    const OPTION_STRATEGY = 'strategy';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param Environment $environment
     * @param FlagManager $flagManager
     */
    public function __construct(
        Environment $environment,
        FlagManager $flagManager
    ) {
        $this->environment = $environment;
        $this->flagManager = $flagManager;
    }

    /**
     * Returns list of recoverable directories with recover strategy types.
     *
     * @return array
     */
    public function getList(): array
    {
        $isSymlinkEnabled = $this->environment->isStaticContentSymlinkOn();

        $recoverableDirs = [
            [
                self::OPTION_DIRECTORY => 'app/etc',
                self::OPTION_STRATEGY => StrategyInterface::STRATEGY_COPY
            ],
            [
                self::OPTION_DIRECTORY => 'pub/media',
                self::OPTION_STRATEGY => StrategyInterface::STRATEGY_COPY
            ]
        ];

        if ($this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)) {
            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => 'var/view_preprocessed',
                self::OPTION_STRATEGY => StrategyInterface::STRATEGY_COPY
            ];
            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => 'pub/static',
                self::OPTION_STRATEGY => $isSymlinkEnabled ?
                    StrategyInterface::STRATEGY_SUB_SYMLINK : StrategyInterface::STRATEGY_COPY
            ];
        }

        return $recoverableDirs;
    }
}
