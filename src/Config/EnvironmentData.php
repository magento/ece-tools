<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\System\Variables;
use Magento\MagentoCloud\PlatformVariable\DecoderInterface;

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
     * Environment constructor.
     *
     * @param Variables $systemConfig
     * @param DecoderInterface $decoder
     */
    public function __construct(Variables $systemConfig, DecoderInterface $decoder)
    {
        $this->systemConfig = $systemConfig;
        $this->decoder = $decoder;
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

        return $this->data['application'] = $this->getEnvVar(SystemConfigInterface::VAR_ENV_APPLICATION, []);
    }

    /**
     * Returns name of environment branch
     *
     * @return string
     */
    public function getBranchName(): string
    {
        $envVarName = $this->systemConfig->get(SystemConfigInterface::VAR_ENV_ENVIRONMENT);

        return $this->getEnv($envVarName) ? (string) $this->getEnv($envVarName) : '';
    }
}
