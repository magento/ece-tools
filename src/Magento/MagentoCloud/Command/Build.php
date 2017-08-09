<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
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

    /**
     * @var ProcessInterface
     */
    private $process;

    public function __construct(ProcessInterface $process)
    {
        $this->process = $process;
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
        try {
            $this->env->setStaticDeployInBuild(false);
            $this->env->log($this->env->startingMessage("build"));

            $this->process->execute();

            $this->deployStaticContent();
        } catch (\Exception $exception) {
            $output->writeln($exception->getMessage());

            return $exception->getCode();
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
}
