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

            $this->clearInitDir();
            $this->env->execute('rm -rf app/etc/env.php');
            $this->backupToInit();
        } catch (\Exception $exception) {
            $output->writeln($exception->getMessage());

            return $exception->getCode();
        }
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
}
