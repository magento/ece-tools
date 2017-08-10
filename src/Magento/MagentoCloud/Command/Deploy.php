<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Config\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * CLI command for deploy hook. Responsible for installing/updating/configuring Magento
 */
class Deploy extends Command
{

    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPassword;

    private $magentoApplicationMode;
    private $cleanStaticViewFiles;
    private $staticDeployThreads;
    private $staticDeployExcludeThemes = [];
    private $adminLocale;
    private $doDeployStaticContent;

    private $verbosityLevel;

    /**
     * @var Environment
     */
    private $env;

    /**
     * @var ProcessInterface
     */
    private $process;

    public function __construct(ProcessInterface $process, Environment $environment)
    {
        $this->process = $process;
        $this->env = $environment;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploys application');

        parent::configure();
    }

    /**
     * Deploy application: copy writable directories back, install or update Magento data.
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->env->log('Starting deploy.');

            $this->process->execute();

            $this->staticContentDeploy();

            $this->env->log('Deployment complete.');
        } catch (\Exception $exception) {
            return $exception->getCode();
        }

        return 0;
    }

    /**
     * Executes database query
     *
     * @param string $query
     * $query must be completed, finished with semicolon (;)
     * @return mixed
     */
    private function executeDbQuery($query)
    {
        $password = strlen($this->dbPassword) ? sprintf('-p%s', $this->dbPassword) : '';

        return $this->env->execute("mysql -u $this->dbUser -h $this->dbHost -e \"$query\" $password $this->dbName");
    }

    /**
     *  This function deploys the static content.
     *  Moved this from processMagentoMode() to its own function because we changed the order to have
     *  processMagentoMode called before the install.  Static content deployment still needs to happen after install.
     */
    private function staticContentDeploy()
    {
        if ($this->magentoApplicationMode == self::MAGENTO_PRODUCTION_MODE) {
            /* Workaround for MAGETWO-58594: disable redis cache before running static deploy, re-enable after */
            if ($this->doDeployStaticContent) {
                $this->deployStaticContent();
            }
        }
    }

    private function deployStaticContent()
    {
        // Clear old static content if necessary
        if ($this->cleanStaticViewFiles) {
            $this->env->removeStaticContent();
        }
        $this->env->log("Generating fresh static content");
        $this->generateFreshStaticContent();
    }

    private function generateFreshStaticContent()
    {
        $this->env->execute('touch ' . MAGENTO_ROOT . 'pub/static/deployed_version.txt');
        /* Enable maintenance mode */
        $this->env->log("Enabling Maintenance mode.");
        $this->env->execute("php ./bin/magento maintenance:enable {$this->verbosityLevel}");

        /* Generate static assets */
        $this->env->log("Extract locales");

        $excludeThemesOptions = '';
        if ($this->staticDeployExcludeThemes) {
            $themes = preg_split("/[,]+/", $this->staticDeployExcludeThemes);
            if (count($themes) > 1) {
                $excludeThemesOptions = "--exclude-theme=" . implode(' --exclude-theme=', $themes);
            } elseif (count($themes) === 1) {
                $excludeThemesOptions = "--exclude-theme=" . $themes[0];
            }
        }

        $jobsOption = $this->staticDeployThreads
            ? "--jobs={$this->staticDeployThreads}"
            : '';

        $locales = implode(' ', $this->getLocales());
        $logMessage = $locales ? "Generating static content for locales: $locales" : "Generating static content.";
        $this->env->log($logMessage);

        // @codingStandardsIgnoreStart
        $this->env->execute(
            "php ./bin/magento setup:static-content:deploy  -f $jobsOption $excludeThemesOptions $locales {$this->verbosityLevel}"
        );
        // @codingStandardsIgnoreEnd

        /* Disable maintenance mode */
        $this->env->execute("php ./bin/magento maintenance:disable {$this->verbosityLevel}");
        $this->env->log("Maintenance mode is disabled.");
    }


    /**
     * Gets locales from DB which are set to stores and admin users.
     * Adds additional default 'en_US' locale to result, if it does't exist yet in defined list.
     *
     * @return array List of locales. Returns empty array in case when no locales are defined in DB
     * ```php
     * [
     *     'en_US',
     *     'fr_FR'
     * ]
     * ```
     */
    private function getLocales()
    {
        $locales = [];

        $query = 'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' '
            . 'UNION SELECT interface_locale FROM admin_user;';
        $output = $this->executeDbQuery($query);

        if (is_array($output) && count($output) > 1) {
            //first element should be shifted as it is the name of column
            array_shift($output);
            $locales = $output;

            if (!in_array($this->adminLocale, $locales)) {
                $locales[] = $this->adminLocale;
            }
        }

        return $locales;
    }

}
