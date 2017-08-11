<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Psr\Log\LoggerInterface;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    const STATIC_CONTENT_DEPLOY_FLAG = '/.static_content_deploy';
    const REGENERATE_FLAG = MAGENTO_ROOT . 'var/.regenerate';

    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    public $writableDirs = ['var', 'app/etc', 'pub/media'];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return isset($_ENV[$key]) ? json_decode(base64_decode($_ENV[$key])) : $default;
    }

    /**
     * Get routes information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRoutes()
    {
        return $this->get('MAGENTO_CLOUD_ROUTES');
    }

    /**
     * Get relationships information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRelationships()
    {
        return $this->get('MAGENTO_CLOUD_RELATIONSHIPS');
    }

    /**
     * Get relationship information from MagentoCloud environment variable by key.
     *
     * @param string $key
     * @return array
     */
    public function getRelationship($key)
    {
        $relationships = $this->getRelationships();

        return isset($relationships[$key]) ? $relationships[$key] : [];
    }

    /**
     * Get custom variables from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getVariables()
    {
        return $this->get('MAGENTO_CLOUD_VARIABLES');
    }

    /**
     * Checks that static content symlink is on.
     *
     * If STATIC_CONTENT_SYMLINK == disabled return false
     * Returns true by default
     *
     * @return bool
     */
    public function isStaticContentSymlinkOn()
    {
        $var = $this->getVariables();

        return isset($var['STATIC_CONTENT_SYMLINK']) && $var['STATIC_CONTENT_SYMLINK'] == 'disabled'
            ? false : true;
    }

    /**
     * @return string
     */
    public function getVerbosityLevel(): string
    {
        $var = $this->getVariables();

        return isset($var['VERBOSE_COMMANDS']) && $var['VERBOSE_COMMANDS'] == 'enabled'
            ? ' -vvv ' : '';
    }

    public function getApplicationMode()
    {
        $var = $this->getVariables();
        $mode = isset($var['APPLICATION_MODE']) ? $var['APPLICATION_MODE'] : false;
        $mode = in_array($mode, [self::MAGENTO_DEVELOPER_MODE, self::MAGENTO_PRODUCTION_MODE])
            ? $mode
            : self::MAGENTO_PRODUCTION_MODE;

        return $mode;
    }

    /**
     * Log message to stream.
     *
     * @param string $message The message string.
     * @return void
     */
    public function log($message)
    {
        $this->logger->info($message);
    }

    /**
     * @param string $command
     * @return array
     * @throws \RuntimeException Throws exception if CLI command returns non-zero status
     */
    public function execute($command)
    {
        $this->log('Command:' . $command);

        $rootPathCommand = sprintf('cd %s && %s', MAGENTO_ROOT, $command);

        exec(
            $rootPathCommand,
            $output,
            $status
        );

        $this->log('Status:' . var_export($status, true));

        if ($output) {
            $this->log('Output:' . var_export($output, true));
        }

        if ($status != 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }

    public function backgroundExecute($command)
    {
        $command = "nohup {$command} 1>/dev/null 2>&1 &";
        $this->log("Execute command in background: $command");
        shell_exec($command);
    }

    public function setStaticDeployInBuild($flag)
    {
        if ($flag) {
            $this->log('Setting flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
            touch(MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        } else {
            if ($this->isStaticDeployInBuild()) {
                $this->log('Removing flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
                unlink(MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
            }
        }
    }

    public function isStaticDeployInBuild()
    {
        return file_exists(MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
    }

    public function removeStaticContent()
    {
        // atomic move within pub/static directory
        $staticContentLocation = realpath(MAGENTO_ROOT . 'pub/static/') . '/';
        $timestamp = time();
        $oldStaticContentLocation = $staticContentLocation . 'old_static_content_' . $timestamp;

        $this->log("Moving out old static content into $oldStaticContentLocation");

        if (!file_exists($oldStaticContentLocation)) {
            mkdir($oldStaticContentLocation);
        }

        $dir = new \DirectoryIterator($staticContentLocation);

        foreach ($dir as $fileInfo) {
            $fileName = $fileInfo->getFilename();
            if (!$fileInfo->isDot() && strpos($fileName, 'old_static_content_') !== 0) {
                $this->log(
                    "Rename " . $staticContentLocation . $fileName
                    . " to " . $oldStaticContentLocation . '/' . $fileName
                );
                rename(
                    $staticContentLocation . '/' . $fileName,
                    $oldStaticContentLocation . '/' . $fileName
                );
            }
        }

        $this->log("Removing $oldStaticContentLocation in the background");
        $this->backgroundExecute("rm -rf $oldStaticContentLocation");

        $preprocessedLocation = realpath(MAGENTO_ROOT . 'var') . '/view_preprocessed';
        if (file_exists($preprocessedLocation)) {
            $oldPreprocessedLocation = $preprocessedLocation . '_old_' . $timestamp;
            $this->log("Rename $preprocessedLocation  to $oldPreprocessedLocation");
            rename($preprocessedLocation, $oldPreprocessedLocation);
            $this->log("Removing $oldPreprocessedLocation in the background");
            $this->backgroundExecute("rm -rf $oldPreprocessedLocation");
        }
    }

    private $componentVersions = [];  // We only want to look up each component version once since it shouldn't change

    private function getVersionOfComponent($component)
    {
        $composerjsonpath = MAGENTO_ROOT . "/vendor/magento/" . $component . "/composer.json";
        $version = null;
        try {
            if (file_exists($composerjsonpath)) {
                $jsondata = json_decode(file_get_contents($composerjsonpath), true);
                if (array_key_exists("version", $jsondata)) {
                    $version = $jsondata["version"];
                }
            }
        } catch (\Exception $e) {
            // If we get an exception (or error), we don't worry because we just won't use the version.
            // Note: We could use Throwable to catch them both, but that only works in PHP >= 7
        } catch (\Error $e) {  // Note: this only works PHP >= 7
        }
        $this->componentVersions[$component] = $version;
    }

    public function versionOfComponent($component)
    {
        if (!array_key_exists($component, $this->componentVersions)) {
            $this->getVersionOfComponent($component);
        }

        return $this->componentVersions[$component];
    }

    public function hasVersionOfComponent($component)
    {
        if (!array_key_exists($component, $this->componentVersions)) {
            $this->getVersionOfComponent($component);
        }

        return !is_null($this->componentVersions[$component]);
    }

    public function startingMessage($starttype)
    {
        $componentsWeCareAbout = ["ece-tools", "magento2-base"];
        $message = "Starting " . $starttype . ".";
        $first = true;
        foreach ($componentsWeCareAbout as $component) {
            if ($this->hasVersionOfComponent($component)) {
                if ($first) {
                    $first = false;
                    $message .= " (";
                } else {
                    $message .= ", ";
                }
                $message .= $component . " version: " . $this->versionOfComponent($component);
            }
        }
        if (!$first) {
            $message .= ")";
        }

        return $message;
    }

    /**
     * Retrieves writable directories.
     *
     * @return array
     */
    public function getWritableDirectories(): array
    {
        return $this->writableDirs;
    }

    public function isDeployStaticContent(): bool
    {
        $var = $this->getVariables();

        /**
         * Can use environment variable to always disable.
         * Default is to deploy static content if it was not deployed in the build step.
         */
        if (isset($var['DO_DEPLOY_STATIC_CONTENT']) && $var['DO_DEPLOY_STATIC_CONTENT'] == 'disabled') {
            $flag = false;
            $this->logger->info('Flag DO_DEPLOY_STATIC_CONTENT is set to disabled');
        } else {
            $flag = !$this->isStaticDeployInBuild();
            $this->logger->info('Flag DO_DEPLOY_STATIC_CONTENT is set to ' . $flag);
        }

        return $flag;
    }

    public function getStaticDeployThreadsCount(): int
    {
        /**
         * Use 1 for PAAS environment.
         */
        $staticDeployThreads = 1;
        $var = $this->getVariables();

        if (isset($var['STATIC_CONTENT_THREADS'])) {
            $staticDeployThreads = (int)$var['STATIC_CONTENT_THREADS'];
        } elseif (isset($_ENV['STATIC_CONTENT_THREADS'])) {
            $staticDeployThreads = (int)$_ENV['STATIC_CONTENT_THREADS'];
        } elseif (isset($_ENV['MAGENTO_CLOUD_MODE']) && $_ENV['MAGENTO_CLOUD_MODE'] === 'enterprise') {
            $staticDeployThreads = 3;
        }

        return $staticDeployThreads;
    }

    public function getAdminLocale(): string
    {
        $var = $this->getVariables();

        return isset($var['ADMIN_LOCALE']) ? $var['ADMIN_LOCALE'] : 'en_US';
    }

    public function doCleanStaticFiles(): bool
    {
        $var = $this->getVariables();

        return isset($var['CLEAN_STATIC_FILES']) && $var['CLEAN_STATIC_FILES'] == 'disabled' ? false : true;
    }

    public function getStaticDeployExcludeThemes()
    {
        $var = $this->getVariables();

        return isset($var['STATIC_CONTENT_EXCLUDE_THEMES']) ? $var['STATIC_CONTENT_EXCLUDE_THEMES'] : [];
    }
}
