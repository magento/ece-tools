<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Package\MagentoVersion;

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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     *
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param Environment $environment
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        Environment $environment,
        FlagManager $flagManager,
        DeployInterface $stageConfig,
        MagentoVersion $magentoVersion
    ) {
        $this->environment = $environment;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Returns list of recoverable directories with recover strategy types.
     *
     * @return array
     */
    public function getList(): array
    {
        $isSymlinkEnabled = $this->stageConfig->get(DeployInterface::VAR_STATIC_CONTENT_SYMLINK);

        $recoverableDirs = [
            [
                self::OPTION_DIRECTORY => 'app/etc',
                self::OPTION_STRATEGY => StrategyInterface::STRATEGY_COPY,
            ],
            [
                self::OPTION_DIRECTORY => 'pub/media',
                self::OPTION_STRATEGY => StrategyInterface::STRATEGY_COPY,
            ],
        ];

        if ($this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)) {
            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => 'var/view_preprocessed',
                self::OPTION_STRATEGY => StrategyInterface::STRATEGY_COPY,
            ];
            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => 'pub/static',
                self::OPTION_STRATEGY => $isSymlinkEnabled ?
                    StrategyInterface::STRATEGY_SUB_SYMLINK : StrategyInterface::STRATEGY_COPY,
            ];
        }

        /**
         * Magento 2.1 related directories.
         */
        if ($this->magentoVersion->isBetween('2.1', '2.2')) {
            $diStrategy = $this->stageConfig->get(DeployInterface::VAR_GENERATED_CODE_SYMLINK)
                ? StrategyInterface::STRATEGY_SYMLINK
                : StrategyInterface::STRATEGY_COPY;

            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => 'var/di',
                self::OPTION_STRATEGY => $diStrategy,
            ];
            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => 'var/generation',
                self::OPTION_STRATEGY => $diStrategy,
            ];
        }

        return $recoverableDirs;
    }
}
