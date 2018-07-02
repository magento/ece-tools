<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud;

use Composer\Composer;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\App\Container;
use Magento\MagentoCloud\Command;
use Magento\MagentoCloud\Package\Manager as PackageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var ContainerInterface|Container
     */
    private $container;

    /**
     * @param ContainerInterface|Container $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct(
            $container->create(Composer::class)->getPackage()->getPrettyName(),
            $container->create(Composer::class)->getPackage()->getPrettyVersion()
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
     * Inject a log statement before each command.
     *
     * {@inheritdoc}
     */
    protected function doRunCommand(SymfonyCommand $command, InputInterface $input, OutputInterface $output)
    {
        $packageManager = $this->getContainer()->get(PackageManager::class);

        $this->getContainer()->get(LoggerInterface::class)
            ->notice('Starting ' . $command->getName() . '. ' . $packageManager->getPrettyInfo());

        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        return array_merge(
            parent::getDefaultCommands(),
            [
                $this->container->create(Command\Build::class),
                $this->container->create(Command\Deploy::class),
                $this->container->create(Command\ConfigDump::class),
                $this->container->create(Command\DbDump::class),
                $this->container->create(Command\PostDeploy::class),
                $this->container->create(Command\CronUnlock::class),
                $this->container->create(Command\BackupRestore::class),
                $this->container->create(Command\BackupList::class),
                $this->container->create(Command\ApplyPatches::class),
                $this->container->create(Command\Dev\UpdateComposer::class),
                $this->container->create(Command\Wizard\ScdOnDemand::class),
                $this->container->create(Command\Wizard\ScdOnBuild::class),
                $this->container->create(Command\Wizard\ScdOnDeploy::class),
                $this->container->create(Command\ModuleRefresh::class),
                $this->container->create(Command\Wizard\IdealState::class),
                $this->container->create(Command\Wizard\MasterSlave::class),
                $this->container->create(Command\Docker\Build::class),
            ]
        );
    }
}
