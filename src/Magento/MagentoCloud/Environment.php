<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    const MAGENTO_ROOT = __DIR__ . '/../../../../../../';
    const STATIC_CONTENT_DEPLOY_FLAG = '/.static_content_deploy';
    const REGENERATE_FLAG = self::MAGENTO_ROOT . 'var/.regenerate';

    /**
     * Deploy log file.
     */
    const DEPLOY_LOG = self::MAGENTO_ROOT . 'var/log/cloud_deploy.log';

    public $writableDirs = ['var', 'app/etc', 'pub/media'];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new Logger('default');
        $this->logger->pushHandler(new StreamHandler(static::DEPLOY_LOG));
        $this->logger->pushHandler(new StreamHandler('php://stdout'));
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
     * @param array $context The context array.
     * @return void
     */
    public function log($message, array $context = [])
    {
        $this->logger->notice($message, $context);
    }

    public function execute($command)
    {
        $this->log('Command: ' . $command);

        exec(
            $command,
            $output,
            $status
        );

        $this->log('Command: ' . $command, [
            'status' => $status,
            'output' => $output
        ]);

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
                $this->log("Rename " . $staticContentLocation . $fileName . " to " . $oldStaticContentLocation . '/' . $fileName);
                rename($staticContentLocation . '/' . $fileName, $oldStaticContentLocation . '/' . $fileName);
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
}
