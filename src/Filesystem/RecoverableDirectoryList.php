<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection;
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
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var GlobalSection
     */
    private $globalSection;

    /**
     * @param Environment $environment
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     * @param MagentoVersion $magentoVersion
     * @param DirectoryList $directoryList
     * @param GlobalSection $globalSection
     */
    public function __construct(
        Environment $environment,
        FlagManager $flagManager,
        DeployInterface $stageConfig,
        MagentoVersion $magentoVersion,
        DirectoryList $directoryList,
        GlobalSection $globalSection
    ) {
        $this->environment = $environment;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
        $this->magentoVersion = $magentoVersion;
        $this->directoryList = $directoryList;
        $this->globalSection = $globalSection;
    }

    /**
     * Returns list of recoverable directories with recover strategy types.
     *
     * @return array
     */
    public function getList(): array
    {
        $isSymlinkEnabled = $this->stageConfig->get(DeployInterface::VAR_STATIC_CONTENT_SYMLINK);
        $staticCopyStrategy = $this->stageConfig->get(DeployInterface::VAR_CLEAN_STATIC_FILES)
            ? StrategyInterface::STRATEGY_COPY
            : StrategyInterface::STRATEGY_COPY_SUB_FOLDERS;

        $recoverableDirs = [
            [
                self::OPTION_DIRECTORY => $this->directoryList->getPath(DirectoryList::DIR_ETC, true),
                self::OPTION_STRATEGY => StrategyInterface::STRATEGY_COPY,
            ],
            [
                self::OPTION_DIRECTORY => $this->directoryList->getPath(DirectoryList::DIR_MEDIA, true),
                self::OPTION_STRATEGY => StrategyInterface::STRATEGY_COPY,
            ],
        ];

        if ($this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)) {
            if (!$this->globalSection->get(GlobalSection::VAR_SKIP_HTML_MINIFICATION)) {
                $recoverableDirs[] = [
                    self::OPTION_DIRECTORY => $this->directoryList->getPath(
                        DirectoryList::DIR_VIEW_PREPROCESSED,
                        true
                    ),
                    self::OPTION_STRATEGY => $staticCopyStrategy,
                ];
            }
            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => $this->directoryList->getPath(DirectoryList::DIR_STATIC, true),
                self::OPTION_STRATEGY => $isSymlinkEnabled ?
                    StrategyInterface::STRATEGY_SUB_SYMLINK : $staticCopyStrategy,
            ];
        }

        /**
         * Magento 2.1 related directories.
         */
        if ($this->magentoVersion->satisfies('2.1.*')) {
            $diStrategy = $this->stageConfig->get(DeployInterface::VAR_GENERATED_CODE_SYMLINK)
                ? StrategyInterface::STRATEGY_SYMLINK
                : StrategyInterface::STRATEGY_COPY;

            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => $this->directoryList->getPath(DirectoryList::DIR_GENERATED_METADATA, true),
                self::OPTION_STRATEGY => $diStrategy,
            ];
            $recoverableDirs[] = [
                self::OPTION_DIRECTORY => $this->directoryList->getPath(DirectoryList::DIR_GENERATED_CODE, true),
                self::OPTION_STRATEGY => $diStrategy,
            ];
        }

        return $recoverableDirs;
    }
}
