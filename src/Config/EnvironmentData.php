<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\System\Variables;
use Magento\MagentoCloud\PlatformVariable\DecoderInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Yaml;

/**
 * Returns cloud environment data.
 */
class EnvironmentData implements EnvironmentDataInterface
{
    /**
     * @var Variables
     */
    private $systemConfig;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;

    /**
     * Environment constructor.
     *
     * @param Variables $systemConfig
     * @param DecoderInterface $decoder
     * @param FileList $fileList
     * @param File $file
     */
    public function __construct(
        Variables $systemConfig,
        DecoderInterface $decoder,
        FileList $fileList,
        File $file
    ) {
        $this->systemConfig = $systemConfig;
        $this->decoder = $decoder;
        $this->fileList = $fileList;
        $this->file = $file;
    }

    /**
     * Method is used for getting environment variables.
     * If variable is exist it will be decoded by DecoderInterface::decode() method.
     * Returns $default argument if not found.
     *
     * @param string $name
     * @param mixed $default
     * @return array|string|int|null
     */
    private function getEnvVar(string $name, $default = null)
    {
        $value = $this->getEnv($this->systemConfig->get($name));
        if (false === $value) {
            return $default;
        }

        return $this->decoder->decode($value);
    }

    /**
     * @inheritDoc
     */
    public function getEnv(string $key)
    {
        return $_ENV[$key] ?? getenv($key);
    }

    /**
     * @inheritDoc
     */
    public function getRoutes(): array
    {
        if (isset($this->data['routes'])) {
            return $this->data['routes'];
        }

        return $this->data['routes'] = $this->getEnvVar(SystemConfigInterface::VAR_ENV_ROUTES, []);
    }

    /**
     * @inheritDoc
     */
    public function getRelationships(): array
    {
        if (isset($this->data['relationships'])) {
            return $this->data['relationships'];
        }

        return $this->data['relationships'] = $this->getEnvVar(SystemConfigInterface::VAR_ENV_RELATIONSHIPS, []);
    }

    /**
     * @inheritDoc
     */
    public function getVariables(): array
    {
        if (isset($this->data['variables'])) {
            return $this->data['variables'];
        }

        return $this->data['variables'] = $this->getEnvVar(SystemConfigInterface::VAR_ENV_VARIABLES, []);
    }

    /**
     * @inheritDoc
     */
    public function getApplication(): array
    {
        if (isset($this->data['application'])) {
            return $this->data['application'];
        }

        $application = $this->getEnvVar(SystemConfigInterface::VAR_ENV_APPLICATION, []);

        if (!$application) {
            try {
                $application = Yaml::parse(
                    $this->file->fileGetContents($this->fileList->getAppConfig())
                );
            } catch (FileSystemException $e) {
                // Do nothing as $application needs to be empty
            }
        }

        return $this->data['application'] = $application;
    }

    /**
     * Returns name of environment branch
     * @return string
     *
     */
    public function getBranchName(): string
    {
        $envVarName = $this->systemConfig->get(SystemConfigInterface::VAR_ENV_ENVIRONMENT);

        return $this->getEnv($envVarName) ? (string) $this->getEnv($envVarName) : '';
    }

    /**
     * Returns MAGE_MODE environment variable
     *
     * @return string|null
     */
    public function getMageMode(): ?string
    {
        if (isset($this->data['mage-mode'])) {
            return $this->data['mage-mode'];
        }

        return $this->data['mage-mode'] = $this->getEnv('MAGE_MODE') ?: null;
    }
}
