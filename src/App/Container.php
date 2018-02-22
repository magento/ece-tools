<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\DbDump;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Command\Prestart;
use Magento\MagentoCloud\Command\PostDeploy;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator as ConfigValidator;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\ReadConnection;
use Magento\MagentoCloud\Filesystem\DirectoryCopier;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Flag;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessComposite;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\DbDump as DbDumpProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;
use Magento\MagentoCloud\Process\ConfigDump as ConfigDumpProcess;
use Magento\MagentoCloud\Process\Prestart as PrestartProcess;
use Magento\MagentoCloud\Process\PostDeploy as PostDeployProcess;
use Psr\Container\ContainerInterface;
use Magento\MagentoCloud\Process;

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
        $this->container->singleton(DirectoryList::class);
        $this->container->singleton(FileList::class);
        $this->container->singleton(\Composer\Composer::class, function () use ($systemList) {
            $composerFactory = new \Composer\Factory();
            $composerFile = file_exists($systemList->getMagentoRoot() . '/composer.json')
                ? $systemList->getMagentoRoot() . '/composer.json'
                : $systemList->getRoot() . '/composer.json';

            return $composerFactory->createComposer(
                new \Composer\IO\BufferIO(),
                $composerFile,
                false,
                $systemList->getMagentoRoot()
            );
        });
        $this->container->singleton(
            Flag\Pool::class,
            function () {
                return new Flag\Pool([
                    Flag\Manager::FLAG_REGENERATE => 'var/.regenerate',
                    Flag\Manager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD => '.static_content_deploy',
                    Flag\Manager::FLAG_STATIC_CONTENT_DEPLOY_PENDING => 'var/.static_content_deploy_pending',
                ]);
            }
        );
        /**
         * Interface to implementation binding.
         */
        $this->container->singleton(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\DB\DumpInterface::class,
            \Magento\MagentoCloud\DB\Dump::class
        );
        $this->container->singleton(\Magento\MagentoCloud\Config\Environment::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\State::class);
        $this->container->singleton(\Magento\MagentoCloud\App\Logger\Pool::class);
        $this->container->singleton(\Psr\Log\LoggerInterface::class, \Magento\MagentoCloud\App\Logger::class);
        $this->container->singleton(\Magento\MagentoCloud\Package\Manager::class);
        $this->container->singleton(\Magento\MagentoCloud\Package\MagentoVersion::class);
        $this->container->singleton(\Magento\MagentoCloud\Util\UrlManager::class);
        $this->container->singleton(
            \Magento\MagentoCloud\DB\ConnectionInterface::class,
            \Magento\MagentoCloud\DB\Connection::class
        );
        $this->container->singleton(DirectoryCopier\CopyStrategy::class);
        $this->container->singleton(DirectoryCopier\SymlinkStrategy::class);
        $this->container->singleton(DirectoryCopier\StrategyFactory::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Stage\Build::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Stage\Deploy::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\RepositoryFactory::class);
        $this->container->singleton(
            \Magento\MagentoCloud\Config\Stage\BuildInterface::class,
            \Magento\MagentoCloud\Config\Stage\Build::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\Config\Stage\DeployInterface::class,
            \Magento\MagentoCloud\Config\Stage\Deploy::class
        );
        /**
         * Contextual binding.
         */
        $this->container->when(Build::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(BuildProcess\PreBuild::class),
                        $this->container->make(BuildProcess\PrepareModuleConfig::class),
                        $this->container->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_CRITICAL => [
                                    $this->container->make(ConfigValidator\Build\ConfigFileExists::class),
                                ],
                                ValidatorInterface::LEVEL_WARNING => [
                                    $this->container->make(ConfigValidator\Build\ConfigFileStructure::class),
                                ],
                            ],
                        ]),
                        $this->container->make(BuildProcess\ApplyPatches::class),
                        $this->container->make(BuildProcess\MarshallFiles::class),
                        $this->container->make(BuildProcess\CopySampleData::class),
                        $this->container->make(BuildProcess\CompileDi::class),
                        $this->container->make(BuildProcess\ComposerDumpAutoload::class),
                        $this->container->make(BuildProcess\DeployStaticContent::class),
                        $this->container->make(BuildProcess\CompressStaticContent::class),
                        $this->container->make(BuildProcess\ClearInitDirectory::class),
                        $this->container->make(BuildProcess\BackupData::class),
                    ],
                ]);
            });
        $this->container->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\PreDeploy::class),
                        $this->container->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_CRITICAL => [
                                    $this->container->make(ConfigValidator\Deploy\AdminEmail::class),
                                ],
                            ],
                        ]),
                        $this->container->make(DeployProcess\CreateConfigFile::class),
                        $this->container->make(DeployProcess\SetMode::class),
                        $this->container->make(DeployProcess\InstallUpdate::class),
                        $this->container->make(DeployProcess\DeployStaticContent::class),
                        $this->container->make(DeployProcess\CompressStaticContent::class),
                        $this->container->make(DeployProcess\DisableGoogleAnalytics::class),
                        $this->container->make(DeployProcess\UnlockCronJobs::class),
                        /**
                         * Remove this line after implementation post-deploy hook
                         */
                        $this->container->make(PostDeployProcess\Backup::class),
                        /**
                         * Cache clean process must remain the last one in deploy chain.
                         * Do not add any processes after it.
                         */
                        $this->container->make(Process\CleanCache::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\InstallUpdate\Install::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\InstallUpdate\Install\Setup::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate::class),
                        $this->container->make(DeployProcess\InstallUpdate\Install\ConfigImport::class),
                        $this->container->make(DeployProcess\InstallUpdate\Install\ResetPassword::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\InstallUpdate\Update::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate::class),
                        $this->container->make(DeployProcess\InstallUpdate\Update\SetAdminUrl::class),
                        $this->container->make(DeployProcess\InstallUpdate\Update\Setup::class),
                        $this->container->make(DeployProcess\InstallUpdate\Update\AdminCredentials::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\InstallUpdate\ConfigUpdate::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\CronConsumersRunner::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\DbConnection::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Amqp::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Cache::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Session::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\SearchEngine::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Urls::class),
                    ],
                ]);
            });
        $this->container->when(Prestart::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(PrestartProcess\DeployStaticContent::class),
                        $this->container->make(PrestartProcess\CompressStaticContent::class),
                    ],
                ]);
            });
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
        $this->container->when(ConfigDump::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->make(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(ConfigDumpProcess\Export::class),
                        $this->container->make(ConfigDumpProcess\Generate::class),
                        $this->container->make(ConfigDumpProcess\Import::class),
                    ],
                ]);
            });
        $this->container->when(ConfigDumpProcess\Export::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->make(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(ConfigDumpProcess\Generate::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\PreDeploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\PreDeploy\CleanStaticContent::class),
                        $this->container->make(DeployProcess\PreDeploy\CleanRedisCache::class),
                        $this->container->make(DeployProcess\PreDeploy\CleanFileCache::class),
                        $this->container->make(DeployProcess\PreDeploy\RestoreWritableDirectories::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(DeployProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
        $this->container->when(PrestartProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(PrestartProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
        $this->container->when(BuildProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(BuildProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
        $this->container->when(DbDump::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DbDumpProcess\DbDump::class),
                    ],
                ]);
            });
        $this->container->when(\Magento\MagentoCloud\DB\Dump::class)
            ->needs(ConnectionInterface::class)
            ->give(ReadConnection::class);
        $this->container->when(PostDeploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->make(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(PostDeployProcess\Backup::class),
                        $this->container->make(PostDeployProcess\CleanCache::class),
                    ],
                ]);
            });
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        return $this->container->make($id);
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * Register a binding with the container.
     *
     * @param string|array $abstract
     * @param \Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function set($abstract, $concrete, bool $shared = true)
    {
        $this->container->forgetInstance($abstract);
        $this->container->bind($abstract, $concrete, $shared);
    }

    /**
     * Creates instance with params.
     *
     * @param string $abstract The class name to create
     * @param array $params Associative array of constructor params
     * @return object The resolved object
     */
    public function create(string $abstract, array $params = [])
    {
        return $this->container->make($abstract, $params);
    }
}
