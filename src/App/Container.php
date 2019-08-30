<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Command\CronKill;
use Magento\MagentoCloud\Command\ModuleRefresh;
use Magento\MagentoCloud\Filesystem\Flag;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;
use Magento\MagentoCloud\Process\ProcessComposite;
use Magento\MagentoCloud\Process\ProcessInterface;
use Composer;

/**
 * @inheritdoc
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Container implements ContainerInterface
{
    /**
     * @var \Illuminate\Container\Container
     */
    private $container;

    /**
     * @param string $toolsBasePath
     * @param string $magentoBasePath
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function __construct(string $toolsBasePath, string $magentoBasePath)
    {
        /**
         * Creating concrete container.
         */
        $this->container = new \Illuminate\Container\Container();

        $systemList = new SystemList($toolsBasePath, $magentoBasePath);

        /**
         * Instance configuration.
         */
        $this->container->instance(ContainerInterface::class, $this);
        $this->container->instance(SystemList::class, $systemList);

        /**
         * Binding.
         */
        $this->container->singleton(Composer\Composer::class, static function () use ($systemList) {
            $composerFactory = new \Composer\Factory();
            $composerFile = file_exists($systemList->getMagentoRoot() . '/composer.json')
                ? $systemList->getMagentoRoot() . '/composer.json'
                : $systemList->getRoot() . '/composer.json';

            return $composerFactory->createComposer(
                new Composer\IO\BufferIO(),
                $composerFile,
                false,
                $systemList->getMagentoRoot()
            );
        });
        $this->container->singleton(Flag\Pool::class, static function () {
            return new Flag\Pool([
                Flag\Manager::FLAG_REGENERATE => 'var/.regenerate',
                Flag\Manager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD => '.static_content_deploy',
                Flag\Manager::FLAG_DEPLOY_HOOK_IS_FAILED => 'var/.deploy_is_failed',
            ]);
        });

        /**
         * Interface to implementation binding.
         */
        $this->container->singleton(
            \Magento\MagentoCloud\Config\ConfigInterface::class,
            \Magento\MagentoCloud\Config\Shared::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\DB\DumpInterface::class,
            \Magento\MagentoCloud\DB\Dump::class
        );
        $this->container->singleton(
            \Psr\Log\LoggerInterface::class,
            \Magento\MagentoCloud\App\Logger::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\DB\ConnectionInterface::class,
            \Magento\MagentoCloud\DB\Connection::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\Config\Stage\BuildInterface::class,
            \Magento\MagentoCloud\Config\Stage\Build::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\Config\Stage\DeployInterface::class,
            \Magento\MagentoCloud\Config\Stage\Deploy::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\Config\Stage\PostDeployInterface::class,
            \Magento\MagentoCloud\Config\Stage\PostDeploy::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\View\RendererInterface::class,
            \Magento\MagentoCloud\View\TwigRenderer::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\PlatformVariable\DecoderInterface::class,
            \Magento\MagentoCloud\PlatformVariable\Decoder::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\Config\Database\ConfigInterface::class,
            \Magento\MagentoCloud\Config\Database\MergedConfig::class
        );

        $this->container->when(DeployProcess\InstallUpdate\ConfigUpdate\Urls::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Urls\Database::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Urls\Environment::class),
                    ],
                ]);
            });

        $this->container->when(CronKill::class)
            ->needs(ProcessInterface::class)
            ->give(DeployProcess\CronProcessKill::class);
        $this->container->when(ModuleRefresh::class)
            ->needs(ProcessInterface::class)
            ->give(BuildProcess\RefreshModules::class);
    }

    /**
     * {@inheritdoc}
     *
     * @see create() For factory-like usage
     */
    public function get($id)
    {
        return $this->container->make($id);
    }

    /**
     * @inheritdoc
     */
    public function has($id): bool
    {
        return $this->container->has($id);
    }

    /**
     * @inheritdoc
     */
    public function set(string $abstract, $concrete, bool $shared = true)
    {
        $this->container->forgetInstance($abstract);
        $this->container->bind($abstract, $concrete, $shared);
    }

    /**
     * @inheritdoc
     */
    public function create(string $abstract, array $params = [])
    {
        return $this->container->make($abstract, $params);
    }
}
