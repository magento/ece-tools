<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud;

use Composer\Composer;
use Magento\MagentoCloud\Command\ApplyPatches;
use Magento\MagentoCloud\Command\ShowAppliedPatches;
use Magento\MagentoCloud\Command\UnapplyPatches;
use Magento\MagentoCloud\Command\BackupList;
use Magento\MagentoCloud\Command\BackupRestore;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\CronUnlock;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Command\Dev\UpdateComposer;
use Magento\MagentoCloud\Command\Prestart;
use Magento\MagentoCloud\Command\DbDump;
use Magento\MagentoCloud\Command\PostDeploy;
use Psr\Container\ContainerInterface;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Application extends \Symfony\Component\Console\Application
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

        parent::__construct(
            $container->get(Composer::class)->getPackage()->getPrettyName(),
            $container->get(Composer::class)->getPackage()->getPrettyVersion()
        );
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        return array_merge(
            parent::getDefaultCommands(),
            [
                $this->container->get(Build::class),
                $this->container->get(Deploy::class),
                $this->container->get(ConfigDump::class),
                $this->container->get(Prestart::class),
                $this->container->get(DbDump::class),
                $this->container->get(PostDeploy::class),
                $this->container->get(CronUnlock::class),
                $this->container->get(BackupRestore::class),
                $this->container->get(BackupList::class),
                $this->container->get(ApplyPatches::class),
                $this->container->get(UnapplyPatches::class),
                $this->container->get(ShowAppliedPatches::class),
                $this->container->get(UpdateComposer::class),
            ]
        );
    }
}
