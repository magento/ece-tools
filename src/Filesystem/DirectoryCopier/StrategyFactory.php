<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Creates instance of CopierInterface
 */
class StrategyFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
                $strategyInstance = $this->container->get(CopyStrategy::class);
                break;
            case StrategyInterface::STRATEGY_COPY_SUB_FOLDERS:
                $strategyInstance = $this->container->get(CopySubFolderStrategy::class);
                break;
            case StrategyInterface::STRATEGY_SYMLINK:
                $strategyInstance = $this->container->get(SymlinkStrategy::class);
                break;
            case StrategyInterface::STRATEGY_SUB_SYMLINK:
                $strategyInstance = $this->container->get(SubSymlinkStrategy::class);
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Strategy "%s" doesn\'t exist', $strategy)
                );
        }

        return $strategyInstance;
    }
}
