<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Environment;

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

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('magento-cloud:build')
            ->setDescription('Invokes set of steps to build source code for the Magento on the Magento Cloud');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->buildOptions = $this->parseBuildOptions();
        $this->env = new Environment();
        $this->build();
        $buildVerbosityLevel = $this->getBuildOption('VERBOSE_COMMANDS');
        $this->verbosityLevel = isset($buildVerbosityLevel) && $buildVerbosityLevel == 'enabled' ? ' -vv ' : '';
    }

    private function build()
    {
        $this->env->setStaticDeployInBuild(false);
        $this->env->log("Start build.");
        $this->applyMccPatches();
        $this->applyCommittedPatches();
        $this->composerDumpAutoload();
        $this->compileDI();
        $this->deployStaticContent();
        $this->clearInitDir();
        $this->env->execute('rm -rf app/etc/env.php');
        $this->env->execute('rm -rf app/etc/config.php');
        $this->backupToInit();
    }

    /**
     * Writable directories will be erased when the writable filesystem is mounted to them. This
     * step backs them up to ./init/
     */
    private function backupToInit()
    {
        $this->env->log("Copying writable directories to temp directory.");
        foreach ($this->env->writableDirs as $dir) {
            $this->env->execute(sprintf('mkdir -p init/%s', $dir));
            $this->env->execute(sprintf('mkdir -p %s', $dir));

            if (count(scandir($dir)) >  2) {
                $this->env->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R %s/* ./init/%s/"', $dir, $dir));
                $this->env->execute(sprintf('rm -rf %s', $dir));
                $this->env->execute(sprintf('mkdir -p %s', $dir));
            }
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
        }
    }

    private function flatten($array, $prefix='')
    {
        $result = [];
        foreach($array as $key=>$value) {
            if(is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . '/');
            }
            else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    private function filter($array, $pattern)
    {
        $filteredResult = [];
        $length = strlen($pattern);
        foreach ($array as $key => $value) {
            if (substr($key, -$length) === $pattern) {
                $filteredResult[$key] = $value;
            }
        }
        return array_values($filteredResult);
    }

    public function deployStaticContent()
    {
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.local.php';
        if (file_exists($configFile) && !$this->getBuildOption(self::BUILD_OPT_SKIP_SCD)) {
            $config = include $configFile;

            $locales = $this->filter($this->flatten($config), 'general/locale/code');

            $SCDLocales = implode(' ', array_unique($locales));

            $excludeThemesOptions = $this->getBuildOption(self::BUILD_OPT_SCD_EXCLUDE_THEMES)
                ? "--exclude-theme=" . implode(' --exclude-theme=', $this->getBuildOption(self::BUILD_OPT_SCD_EXCLUDE_THEMES))
                : '';
            $jobsOption = $this->getBuildOption(self::BUILD_OPT_SCD_THREADS) ? "--jobs={$this->getBuildOption(self::BUILD_OPT_SCD_THREADS)}" : '';

            try {
                $logMessage = $SCDLocales ? "Generating static content for locales: $SCDLocales" : "Generating static content.";
                $logMessage .= $excludeThemesOptions ? "\nExcluding Themes: $excludeThemesOptions" : "";
                $logMessage .= $jobsOption ? "\nUsing $jobsOption Threads" : "";

                $this->env->log($logMessage);

                $this->env->execute(
                    "/usr/bin/php ./bin/magento setup:static-content:deploy $jobsOption $excludeThemesOptions $SCDLocales {$this->verbosityLevel}"
                );
                $this->env->setStaticDeployInBuild(true);
            } catch (\Exception $e) {
                $this->env->log($e->getMessage());
            }
        } else {
            $this->env->log("Skipping static content deploy");
        }
    }

    /**
     * Apply patches distributed through the magento-cloud-configuration file
     */
    private function applyMccPatches()
    {
        $this->env->log("Applying magento-cloud-configuration patches.");
        $this->env->execute('/usr/bin/php ' . Environment::MAGENTO_ROOT . 'vendor/magento/magento-cloud-configuration/patch.php');
    }

    /**
     * Apply patches distributed through the magento-cloud-configuration file
     */
    private function applyCommittedPatches()
    {
        $patchesDir = Environment::MAGENTO_ROOT . 'm2-hotfixes/';
        $this->env->log("Checking if patches exist under " . $patchesDir);
        if (is_dir($patchesDir)) {
            $files = glob($patchesDir . "*");
            sort($files);
            foreach ($files as $file) {
                $cmd = 'git apply '  . $file;
                $this->env->execute($cmd);
            }
        }
    }

    private function compileDI()
    {
        $this->env->execute('rm -rf var/generation/*');
        $this->env->execute('rm -rf var/di/*');

        $this->env->log("Enabling all modules");
        $this->env->execute("php ./bin/magento module:enable --all");

        if (!$this->getBuildOption(self::BUILD_OPT_SKIP_DI_COMPILATION)) {
            $this->env->log("Running DI compilation");
            $this->env->execute("php ./bin/magento setup:di:compile {$this->verbosityLevel} ");
        } else {
            $this->env->log("Skip running DI compilation");
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

    private function getBuildOption($key) {
        return isset($this->buildOptions[$key]) ? $this->buildOptions[$key] : false;
    }

    private function composerDumpAutoload()
    {
        $this->env->execute('composer dump-autoload');
    }
}
