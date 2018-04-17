<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud;

use Composer\Composer;
use Magento\MagentoCloud\Command;
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
                $this->container->get(Command\Build::class),
                $this->container->get(Command\Deploy::class),
                $this->container->get(Command\ConfigDump::class),
                $this->container->get(Command\Prestart::class),
                $this->container->get(Command\DbDump::class),
                $this->container->get(Command\PostDeploy::class),
                $this->container->get(Command\CronUnlock::class),
                $this->container->get(Command\BackupRestore::class),
                $this->container->get(Command\BackupList::class),
                $this->container->get(Command\ApplyPatches::class),
                $this->container->get(Command\UnapplyPatches::class),
                $this->container->get(Command\ShowAppliedPatches::class),
                $this->container->get(Command\Dev\UpdateComposer::class),
                $this->container->get(Command\Wizard\ScdOnDemand::class),
                $this->container->get(Command\Wizard\ScdOnBuild::class),
                $this->container->get(Command\Wizard\ScdOnDeploy::class),
            ]
        );
    }
}
