<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud;

use Psr\Log\LoggerInterface;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    const MAGENTO_ROOT = __DIR__ . '/../../../../../../';
    const STATIC_CONTENT_DEPLOY_FLAG = '/.static_content_deploy';
    const REGENERATE_FLAG = self::MAGENTO_ROOT . 'var/.regenerate';

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
     * Get routes information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRoutes()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_ROUTES"]), true);
    }

    /**
     * Get relationships information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRelationships()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_RELATIONSHIPS"]), true);
    }

    /**
     * Get custom variables from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getVariables()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_VARIABLES"]), true);
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

        $rootPathCommand = sprintf('cd %s && %s', Environment::MAGENTO_ROOT, $command);

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
            touch(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        } else {
            if ($this->isStaticDeployInBuild()) {
                $this->log('Removing flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
                unlink(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
            }
        }
    }

    public function isStaticDeployInBuild()
    {
        return file_exists(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
    }

    public function removeStaticContent()
    {
        // atomic move within pub/static directory
        $staticContentLocation = realpath(Environment::MAGENTO_ROOT . 'pub/static/') . '/';
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

        $preprocessedLocation = realpath(Environment::MAGENTO_ROOT . 'var') . '/view_preprocessed';
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
        $composerjsonpath = Environment::MAGENTO_ROOT . "/vendor/magento/" . $component . "/composer.json";
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

    /**
     * Return full path to environment configuration file.
     *
     * @return string The path to configuration file
     */
    public function getConfigFilePath()
    {
        return Environment::MAGENTO_ROOT . 'app/etc/env.php';
    }
}
