<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for build hook. Responsible for preparing the codebase before it's moved to the server.
 */
class Build extends Command
{
    /**
     * Options for build_options.ini
     */
    const BUILD_OPT_SKIP_DI_COMPILATION = 'skip_di_compilation';
    const BUILD_OPT_SKIP_DI_CLEARING = 'skip_di_clearing';
    const BUILD_OPT_SCD_EXCLUDE_THEMES = 'exclude_themes';
    const BUILD_OPT_SCD_THREADS = 'scd_threads';
    const BUILD_OPT_SKIP_SCD = 'skip_scd';

    /**
     * @var Environment
     */
    private $env;

    /**
     * @var array
     */
    private $buildOptions;

    /**
     * @var string
     */
    private $verbosityLevel;

    public function __construct()
    {
        $this->buildOptions = $this->parseBuildOptions();
        $this->env = new Environment();
        $buildVerbosityLevel = $this->getBuildOption('VERBOSE_COMMANDS');
        isset($buildVerbosityLevel) && $buildVerbosityLevel == 'enabled'
            ? $this->env->log("Verbosity level is set to " . $buildVerbosityLevel)
            : $this->env->log("Verbosity level is not set");
        $this->verbosityLevel = isset($buildVerbosityLevel) && $buildVerbosityLevel == 'enabled' ? ' -vv ' : '';

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Builds application');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->env->setStaticDeployInBuild(false);
        $this->env->log($this->env->startingMessage("build"));
        $this->applyPatches();
        $this->marshallingFiles();
        $this->compileDI();
        $this->composerDumpAutoload();
        $this->deployStaticContent();
        $this->clearInitDir();
        $this->env->execute('rm -rf app/etc/env.php');
        $this->backupToInit();
    }

    /**
     * Writable directories will be erased when the writable filesystem is mounted to them. This
     * step backs them up to ./init/
     */
    private function backupToInit()
    {
        if (file_exists(Environment::REGENERATE_FLAG)) {
            $this->env->log("Removing .regenerate flag");
            unlink(Environment::REGENERATE_FLAG);
        }

        if ($this->env->isStaticDeployInBuild()) {
            $this->env->log("Moving static content to init directory");
            $this->env->execute('mkdir -p ./init/pub/');
            if (file_exists('./init/pub/static')) {
                $this->env->log("Remove ./init/pub/static");
                unlink('./init/pub/static');
            }
            $this->env->execute('cp -R ./pub/static/ ./init/pub/static');
            copy(
                Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG,
                Environment::MAGENTO_ROOT . 'init/' . Environment::STATIC_CONTENT_DEPLOY_FLAG
            );
        } else {
            $this->env->log("No file " . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        }

        $this->env->log("Copying writable directories to temp directory.");
        foreach ($this->env->writableDirs as $dir) {
            $this->env->execute(sprintf('mkdir -p init/%s', $dir));
            $this->env->execute(sprintf('mkdir -p %s', $dir));

            if (count(scandir(Environment::MAGENTO_ROOT . $dir)) > 2) {
                $this->env->execute(
                    sprintf('/bin/bash -c "shopt -s dotglob; cp -R %s/* ./init/%s/"', $dir, $dir)
                );
                $this->env->execute(sprintf('rm -rf %s', $dir));
                $this->env->execute(sprintf('mkdir -p %s', $dir));
            }
        }
    }

    private function flatten($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . '/');
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    private function filter($array, $pattern, $ending = true)
    {
        $filteredResult = [];
        $length = strlen($pattern);
        foreach ($array as $key => $value) {
            if ($ending) {
                if (substr($key, -$length) === $pattern) {
                    $filteredResult[$key] = $value;
                }
            } else {
                if (substr($key, 0, strlen($pattern)) === $pattern) {
                    $filteredResult[$key] = $value;
                }
            }
        }
        return array_unique(array_values($filteredResult));
    }

    public function deployStaticContent()
    {
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.php';
        if (file_exists($configFile) && !$this->getBuildOption(self::BUILD_OPT_SKIP_SCD)) {
            $config = include $configFile;

            $flattenedConfig = $this->flatten($config);
            $websites = $this->filter($flattenedConfig, 'scopes/websites', false);
            $stores = $this->filter($flattenedConfig, 'scopes/stores', false);

            $locales = [];
            $locales = array_merge($locales, $this->filter($flattenedConfig, 'general/locale/code'));
            $locales = array_merge(
                $locales,
                $this->filter($flattenedConfig, 'admin_user/locale/code', false)
            );
            $locales[] = 'en_US';
            $locales = array_unique($locales);

            if (count($stores) === 0 && count($websites) === 0) {
                $this->env->log("No stores/website/locales found in config.php");
                $this->env->setStaticDeployInBuild(false);
                return;
            }

            $SCDLocales = implode(' ', $locales);

            $excludeThemesOptions = '';
            if ($this->getBuildOption(self::BUILD_OPT_SCD_EXCLUDE_THEMES)) {
                $themes = preg_split("/[,]+/", $this->getBuildOption(self::BUILD_OPT_SCD_EXCLUDE_THEMES));
                if (count($themes) > 1) {
                    $excludeThemesOptions = "--exclude-theme=" . implode(' --exclude-theme=', $themes);
                } elseif (count($themes) === 1) {
                    $excludeThemesOptions = "--exclude-theme=" . $themes[0];
                }
            }

            $threads = $this->getBuildOption(self::BUILD_OPT_SCD_THREADS)
                ? "{$this->getBuildOption(self::BUILD_OPT_SCD_THREADS)}"
                : '0';

            try {
                $logMessage = $SCDLocales
                    ? "Generating static content for locales: $SCDLocales"
                    : "Generating static content.";
                $logMessage .= $excludeThemesOptions ? "\nExcluding Themes: $excludeThemesOptions" : "";
                $logMessage .= $threads ? "\nUsing $threads Threads" : "";

                $this->env->log($logMessage);

                $parallelCommands = "";
                foreach ($locales as $locale) {
                    // @codingStandardsIgnoreStart
                    $parallelCommands .= "php ./bin/magento setup:static-content:deploy -f $excludeThemesOptions $locale {$this->verbosityLevel}" . '\n';
                    // @codingStandardsIgnoreEnd
                }
                $this->env->execute("printf '$parallelCommands' | xargs -I CMD -P " . (int)$threads . " bash -c CMD");


                $this->env->setStaticDeployInBuild(true);
            } catch (\Exception $e) {
                $this->env->log($e->getMessage());
                exit(5);
            }
        } else {
            $this->env->log("Skipping static content deploy");
        }
    }

    /**
     * Apply ECE patches as well as patches in m2-hotfixes
     */
    private function applyPatches()
    {
        $this->env->log("Applying patches.");
        $this->env->execute('php vendor/bin/m2-apply-patches');
    }

    /**
     * Marshalls required files.
     */
    private function marshallingFiles()
    {
        $this->env->execute('rm -rf generated/code/*');
        $this->env->execute('rm -rf generated/metadata/*');
        $this->env->execute('rm -rf var/cache');

        copy(Environment::MAGENTO_ROOT . 'app/etc/di.xml', Environment::MAGENTO_ROOT . 'app/di.xml');
        $enterpriseFolder = Environment::MAGENTO_ROOT . 'app/enterprise';
        if (!file_exists($enterpriseFolder)) {
            mkdir($enterpriseFolder, 0777, true);
        }
        copy(
            Environment::MAGENTO_ROOT . 'app/etc/enterprise/di.xml',
            Environment::MAGENTO_ROOT . 'app/enterprise/di.xml'
        );

        $sampleDataDir = Environment::MAGENTO_ROOT . 'vendor/magento/sample-data-media';
        if (file_exists($sampleDataDir)) {
            $this->env->log("Sample data media found. Marshalling to pub/media.");
            $destination = Environment::MAGENTO_ROOT . '/pub/media';
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sampleDataDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    if (!file_exists($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
                        mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                    }
                } else {
                    copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                }
            }
        }
    }

    private function compileDI()
    {
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.php';
        if (file_exists($configFile)) {
            if (!$this->getBuildOption(self::BUILD_OPT_SKIP_DI_COMPILATION)) {
                $this->env->log("Running DI compilation");
                $this->env->execute("php ./bin/magento setup:di:compile {$this->verbosityLevel} ");
            } else {
                $this->env->log("Skip running DI compilation");
            }
        } else {
            $this->env->log(
                "Missing config.php, please run the following commands "
                . "\n 1. bin/magento module:enable --all "
                . "\n 2. git add -f app/etc/config.php "
                . "\n 3. git commit -a -m 'adding config.php' "
                . "\n 4. git push"
            );
            exit(6);
        }
    }

    /**
     * Clear content of temp directory
     */
    private function clearInitDir()
    {
        $this->env->log("Clearing temporary directory.");
        $this->env->execute('rm -rf ../init/*');
    }

    /**
     * Parse optional build_options.ini file in Magento root directory
     */
    private function parseBuildOptions()
    {
        $fileName = Environment::MAGENTO_ROOT . '/build_options.ini';
        return file_exists($fileName)
            ? parse_ini_file(Environment::MAGENTO_ROOT . '/build_options.ini')
            : [];
    }

    private function getBuildOption($key)
    {
        return isset($this->buildOptions[$key]) ? $this->buildOptions[$key] : false;
    }

    private function composerDumpAutoload()
    {
        $this->env->execute('composer dump-autoload -o');
    }
}
