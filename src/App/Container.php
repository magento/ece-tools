<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\CronKill;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ModuleRefresh;
use Magento\MagentoCloud\Command\PostDeploy;
use Magento\MagentoCloud\Config\Database\ConfigInterface;
use Magento\MagentoCloud\Config\Database\MergedConfig;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Validator as ConfigValidator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\DirectoryCopier;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Flag;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;
use Magento\MagentoCloud\Process\PostDeploy as PostDeployProcess;
use Magento\MagentoCloud\Process\ProcessComposite;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\SetProductionMode;

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
        $this->container->singleton(Schema::class);
        $this->container->singleton(DirectoryList::class);
        $this->container->singleton(FileList::class);
        $this->container->singleton(DeployProcess\InstallUpdate\ConfigUpdate\SearchEngine::class);
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
                    Flag\Manager::FLAG_DEPLOY_HOOK_IS_FAILED => 'var/.deploy_is_failed',
                ]);
            }
        );
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
        $this->container->singleton(DirectoryCopier\CopySubFolderStrategy::class);
        $this->container->singleton(DirectoryCopier\SymlinkStrategy::class);
        $this->container->singleton(DirectoryCopier\StrategyFactory::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Build\Reader::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Environment\Reader::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Stage\Build::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Stage\Deploy::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Stage\PostDeploy::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\RepositoryFactory::class);
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

        $this->container->singleton(\Magento\MagentoCloud\Shell\UtilityManager::class);
        $this->container->singleton(
            \Magento\MagentoCloud\View\RendererInterface::class,
            \Magento\MagentoCloud\View\TwigRenderer::class
        );
        $this->container->singleton(
            \Magento\MagentoCloud\PlatformVariable\DecoderInterface::class,
            \Magento\MagentoCloud\PlatformVariable\Decoder::class
        );
        /**
         * Contextual binding.
         */
        $this->container->when(Build::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->get('buildGenerateProcess'),
                        $this->container->get('buildTransferProcess'),
                    ],
                ]);
            });
        $this->container->when(Build\Generate::class)
            ->needs(ProcessInterface::class)
            ->give('buildGenerateProcess');
        $this->container->when(Build\Transfer::class)
            ->needs(ProcessInterface::class)
            ->give('buildTransferProcess');
        $this->container->bind(
            'buildGenerateProcess',
            function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(BuildProcess\PreBuild::class),
                        $this->container->make(SetProductionMode::class),
                        $this->container->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_CRITICAL => [
                                    $this->container->make(ConfigValidator\Build\ComposerFile::class),
                                    $this->container->make(ConfigValidator\Build\StageConfig::class),
                                    $this->container->make(ConfigValidator\Build\BuildOptionsIni::class),
                                ],
                                ValidatorInterface::LEVEL_WARNING => [
                                    $this->container->make(ConfigValidator\Build\ConfigFileExists::class),
                                    $this->container->make(ConfigValidator\Build\DeprecatedBuildOptionsIni::class),
                                    $this->container->make(ConfigValidator\Build\StageConfigDeprecatedVariables::class),
                                    $this->container->make(ConfigValidator\Build\ModulesExists::class),
                                    $this->container->make(ConfigValidator\Build\AppropriateVersion::class),
                                    $this->container->make(ConfigValidator\Build\ScdOptionsIgnorance::class),
                                    $this->container->make(ConfigValidator\IdealState::class),
                                ],
                            ],
                        ]),
                        $this->container->make(BuildProcess\RefreshModules::class),
                        $this->container->make(BuildProcess\ApplyPatches::class),
                        $this->container->make(BuildProcess\MarshallFiles::class),
                        $this->container->make(BuildProcess\CopySampleData::class),
                        $this->container->make(BuildProcess\CompileDi::class),
                        $this->container->make(BuildProcess\ComposerDumpAutoload::class),
                        $this->container->make(BuildProcess\DeployStaticContent::class),
                    ],
                ]);
            }
        );
        $this->container->bind(
            'buildTransferProcess',
            function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(BuildProcess\CompressStaticContent::class),
                        $this->container->make(BuildProcess\ClearInitDirectory::class),
                        $this->container->make(BuildProcess\BackupData::class),
                    ],
                ]);
            }
        );
        $this->container->when(BuildProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(BuildProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
        $this->container->when(BuildProcess\BackupData::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(BuildProcess\BackupData\StaticContent::class),
                        $this->get(BuildProcess\BackupData\WritableDirectories::class),
                    ],
                ]);
            });
        $this->container->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\PreDeploy::class),
                        $this->container->make(DeployProcess\DisableCron::class),
                        $this->container->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_CRITICAL => [
                                    $this->container->make(ConfigValidator\Deploy\DatabaseConfiguration::class),
                                    $this->container->make(ConfigValidator\Deploy\SearchConfiguration::class),
                                    $this->container->make(ConfigValidator\Deploy\ResourceConfiguration::class),
                                    $this->container->make(ConfigValidator\Deploy\SessionConfiguration::class),
                                    $this->container->make(ConfigValidator\Deploy\ElasticSuiteIntegrity::class),
                                ],
                                ValidatorInterface::LEVEL_WARNING => [
                                    $this->container->make(ConfigValidator\Deploy\AdminData::class),
                                    $this->container->make(ConfigValidator\Deploy\PhpVersion::class),
                                    $this->container->make(ConfigValidator\Deploy\SolrIntegrity::class),
                                    $this->container->make(ConfigValidator\Deploy\ElasticSearchUsage::class),
                                    $this->container->make(ConfigValidator\Deploy\ElasticSearchVersion::class),
                                    $this->container->make(ConfigValidator\Deploy\AppropriateVersion::class),
                                    $this->container->make(ConfigValidator\Deploy\ScdOptionsIgnorance::class),
                                    $this->container->make(ConfigValidator\Deploy\DeprecatedVariables::class),
                                    $this->container->make(ConfigValidator\Deploy\RawEnvVariable::class),
                                    $this->container->make(ConfigValidator\Deploy\MagentoCloudVariables::class),
                                    $this->container->make(ConfigValidator\Deploy\JsonFormatVariable::class),
                                    $this->container->make(ConfigValidator\Deploy\ServiceVersion::class),
                                ],
                            ],
                        ]),
                        $this->container->make(DeployProcess\UnlockCronJobs::class),
                        $this->container->make(DeployProcess\SetCryptKey::class),
                        $this->container->make(DeployProcess\InstallUpdate::class),
                        $this->container->make(DeployProcess\DeployStaticContent::class),
                        $this->container->make(DeployProcess\CompressStaticContent::class),
                        $this->container->make(DeployProcess\DisableGoogleAnalytics::class),

                        /**
                         * This process runs processes if only post_deploy hook is not configured.
                         */
                        $this->container->make(DeployProcess\DeployCompletion::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\DeployCompletion::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(PostDeployProcess\EnableCron::class),
                        $this->container->make(PostDeployProcess\Backup::class),
                        $this->container->make(PostDeployProcess\CleanCache::class),
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
                    ],
                ]);
            });
        $this->container->when(DeployProcess\InstallUpdate\ConfigUpdate::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\PrepareConfig::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\CronConsumersRunner::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\DbConnection::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Amqp::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Session::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\SearchEngine::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Urls::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\DocumentRoot::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Lock::class),
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
        $this->container->when(DeployProcess\PreDeploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\PreDeploy\ConfigUpdate\Cache::class),
                        $this->container->make(DeployProcess\PreDeploy\CleanStaticContent::class),
                        $this->container->make(DeployProcess\PreDeploy\CleanViewPreprocessed::class),
                        $this->container->make(DeployProcess\PreDeploy\CleanRedisCache::class),
                        $this->container->make(DeployProcess\PreDeploy\CleanFileCache::class),
                        $this->container->make(DeployProcess\PreDeploy\RestoreWritableDirectories::class),
                        $this->container->make(SetProductionMode::class),
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
        $this->container->when(PostDeploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->make(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_WARNING => [
                                    $this->container->make(ConfigValidator\Deploy\DebugLogging::class),
                                ],
                            ],
                        ]),
                        $this->container->make(PostDeployProcess\EnableCron::class),
                        $this->container->make(PostDeployProcess\Backup::class),
                        $this->container->make(PostDeployProcess\CleanCache::class),
                        $this->container->make(PostDeployProcess\WarmUp::class),
                        $this->container->make(PostDeployProcess\TimeToFirstByte::class),
                    ],
                ]);
            });

        $this->container->when(CronKill::class)
            ->needs(ProcessInterface::class)
            ->give(DeployProcess\BackgroundProcessKill::class);
        $this->container->when(ModuleRefresh::class)
            ->needs(ProcessInterface::class)
            ->give(BuildProcess\RefreshModules::class);

        $this->container->singleton(ConfigInterface::class, MergedConfig::class);
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
