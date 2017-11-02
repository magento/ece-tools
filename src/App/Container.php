<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator as ConfigValidator;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessComposite;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;
use Magento\MagentoCloud\Process\ConfigDump as ConfigDumpProcess;
use Psr\Container\ContainerInterface;

/**
 * @inheritdoc
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Container extends \Illuminate\Container\Container implements ContainerInterface
{
    /**
     * @param string $root
     * @param array $config
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function __construct(string $root, array $config)
    {
        /**
         * Instance configuration.
         */
        $this->singleton(\Magento\MagentoCloud\Filesystem\DirectoryList::class, function () use ($root, $config) {
            return new \Magento\MagentoCloud\Filesystem\DirectoryList($root, $config);
        });
        $this->singleton(\Magento\MagentoCloud\Filesystem\FileList::class);
        $this->singleton(\Composer\Composer::class, function () {
            $fileList = $this->get(\Magento\MagentoCloud\Filesystem\FileList::class);

            return \Composer\Factory::create(
                new \Composer\IO\BufferIO(),
                $fileList->getComposer()
            );
        });
        /**
         * Interface to implementation binding.
         */
        $this->singleton(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );
        $this->singleton(\Magento\MagentoCloud\Config\Environment::class);
        $this->singleton(\Magento\MagentoCloud\Config\Build::class);
        $this->singleton(\Magento\MagentoCloud\Config\Deploy::class);
        $this->singleton(\Psr\Log\LoggerInterface::class, \Magento\MagentoCloud\App\Logger::class);
        $this->singleton(\Magento\MagentoCloud\Package\Manager::class);
        $this->singleton(\Magento\MagentoCloud\Package\MagentoVersion::class);
        $this->singleton(\Magento\MagentoCloud\Util\UrlManager::class);
        $this->singleton(
            \Magento\MagentoCloud\DB\ConnectionInterface::class,
            \Magento\MagentoCloud\DB\Connection::class
        );
        $this->singleton(\Magento\MagentoCloud\Filesystem\FileList::class);
        /**
         * Contextual binding.
         */
        $this->when(Build::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->make(BuildProcess\PreBuild::class),
                        $this->make(BuildProcess\PrepareModuleConfig::class),
                        $this->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_CRITICAL => [
                                    $this->make(ConfigValidator\Build\ConfigFileExist::class)
                                ],
                                ValidatorInterface::LEVEL_WARNING => [
                                    $this->make(ConfigValidator\Build\ConfigFileScd::class)
                                ]
                            ]
                        ]),
                        $this->make(BuildProcess\ApplyPatches::class),
                        $this->make(BuildProcess\MarshallFiles::class),
                        $this->make(BuildProcess\CopySampleData::class),
                        $this->make(BuildProcess\CompileDi::class),
                        $this->make(BuildProcess\ComposerDumpAutoload::class),
                        $this->make(BuildProcess\DeployStaticContent::class),
                        $this->make(BuildProcess\CompressStaticContent::class),
                        $this->make(BuildProcess\ClearInitDirectory::class),
                        $this->make(BuildProcess\BackupData::class),
                    ],
                ]);
            });
        $this->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_CRITICAL => [
                                    $this->make(ConfigValidator\Deploy\AdminEmail::class)
                                ],
                            ]
                        ]),
                        $this->make(DeployProcess\PreDeploy::class),
                        $this->make(DeployProcess\CreateConfigFile::class),
                        $this->make(DeployProcess\SetMode::class),
                        $this->make(DeployProcess\InstallUpdate::class),
                        $this->make(DeployProcess\DeployStaticContent::class),
                        $this->make(DeployProcess\CompressStaticContent::class),
                        $this->make(DeployProcess\DisableGoogleAnalytics::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\InstallUpdate\Install::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->make(DeployProcess\InstallUpdate\Install\Setup::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate::class),
                        $this->make(DeployProcess\InstallUpdate\Install\ConfigImport::class),
                        $this->make(DeployProcess\InstallUpdate\Install\ResetPassword::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\InstallUpdate\Update::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate::class),
                        $this->make(DeployProcess\InstallUpdate\Update\SetAdminUrl::class),
                        $this->make(DeployProcess\InstallUpdate\Update\Setup::class),
                        $this->make(DeployProcess\InstallUpdate\Update\AdminCredentials::class),
                        $this->make(DeployProcess\InstallUpdate\Update\ClearCache::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\InstallUpdate\ConfigUpdate::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\DbConnection::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\Amqp::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\Redis::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\SearchEngine::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\Urls::class),
                    ],
                ]);
            });
        $this->when(ConfigDump::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->make(ProcessComposite::class, [
                    'processes' => [
                        $this->make(ConfigDumpProcess\Export::class),
                        $this->make(ConfigDumpProcess\Generate::class),
                        $this->make(ConfigDumpProcess\Import::class),
                    ],
                ]);
            });
        $this->when(ConfigDumpProcess\Export::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->make(ProcessComposite::class, [
                    'processes' => [
                        $this->make(ConfigDumpProcess\Generate::class),
                    ],
                ]);
            });
        $this->when(ConfigDumpProcess\Generate::class)
            ->needs('$configKeys')
            ->give(function () {
                return [
                    'modules',
                    'scopes',
                    'system/default/general/locale/code',
                    'system/default/dev/static/sign',
                    'system/default/dev/front_end_development_workflow',
                    'system/default/dev/template',
                    'system/default/dev/js',
                    'system/default/dev/css',
                    'system/default/advanced/modules_disable_output',
                    'system/stores',
                    'system/websites',
                ];
            });
        $this->when(DeployProcess\PreDeploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->make(DeployProcess\PreDeploy\RestoreWritableDirectories::class),
                        $this->make(DeployProcess\PreDeploy\CleanRedisCache::class),
                        $this->make(DeployProcess\PreDeploy\CleanFileCache::class),
                        $this->make(DeployProcess\PreDeploy\ProcessStaticContent::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(DeployProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
        $this->when(\Magento\MagentoCloud\Config\Build::class)
            ->needs(\Magento\MagentoCloud\Filesystem\Reader\ReaderInterface::class)
            ->give(\Magento\MagentoCloud\Config\Build\Reader::class);
        $this->when(BuildProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(BuildProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        return $this->resolve($id);
    }
}
