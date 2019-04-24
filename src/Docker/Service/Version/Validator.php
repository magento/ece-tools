<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service\Version;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * Validate Service versions for docker configuration.
 */
class Validator
{
    /**
     * Supported version constraints of services for every Magento version
     */
    const MAGENTO_SUPPORTED_SERVICE_VERSIONS = [
        '<2.2.0' => [
            Config::KEY_PHP => '7.0.2 || 7.0.4 || ~7.0.6 || ~7.1.0',
            Config::KEY_DB => '>=10.0 <10.3',
            Config::KEY_NGINX => '^1.9',
            Config::KEY_VARNISH => '~3.5 || ~4.0',
            Config::KEY_REDIS => '~3.2 || ~4.0 || ~5.0',
            Config::KEY_ELASTICSEARCH => '~1.7 || ~2.4',
            Config::KEY_RABBITMQ => '~3.5',
        ],
        '>=2.2.0 <2.2.8' => [
            Config::KEY_PHP => '~7.0.13 || ~7.1',
            Config::KEY_DB => '>=10.0 <10.3',
            Config::KEY_NGINX => '^1.9',
            Config::KEY_VARNISH => '~4.0 || ~5.0',
            Config::KEY_REDIS => '~3.2 || ~4.0 || ~5.0',
            Config::KEY_ELASTICSEARCH => '~2.4 || ~5.2',
            Config::KEY_RABBITMQ => '~3.5',
        ],
        '>=2.2.8 <2.3.0' => [
            Config::KEY_PHP => '~7.0.13 || ~7.1',
            Config::KEY_DB => '>=10.0 <10.3',
            Config::KEY_NGINX => '^1.9',
            Config::KEY_VARNISH => '~4.0 || ~5.0',
            Config::KEY_REDIS => '~3.2 || ~4.0 || ~5.0',
            Config::KEY_ELASTICSEARCH => '~2.4 || ~5.2 || ~6.5',
            Config::KEY_RABBITMQ => '~3.5',
        ],
        '>=2.3.0' => [
            Config::KEY_PHP => '~7.1.3 || ~7.2.0',
            Config::KEY_DB => '>=10.0 <10.3',
            Config::KEY_NGINX => '^1.9',
            Config::KEY_VARNISH => '~4.0 || ~5.0',
            Config::KEY_REDIS => '~3.2 || ~4.0 || ~5.0',
            Config::KEY_ELASTICSEARCH => '~2.4 || ~5.2 || ~6.5',
            Config::KEY_RABBITMQ => '~3.7',
        ]
    ];

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * List of allowed service versions for current Magento version
     *
     * @var array
     */
    private $supportedVersionList;

    /**
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(MagentoVersion $magentoVersion)
    {
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Validates provided services version for current Magento.
     * Returns empty array if all provided versions are supported. Otherwise, returns warning message for
     * every unsupported service in separate array elements.
     *
     * Example of $serviceVersions argument:
     *
     * ```php
     *  [
     *      'elasticsearch' => '5.6',
     *      'db' => '10.0'
     *  ];
     * ```
     *
     * @param array $serviceVersions List of services and their names which should be validates.
     * @return array List of warning messages. One message for one unsupported service.
     *
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    public function validateVersions($serviceVersions)
    {
        $errors = [];
        foreach ($serviceVersions as $name => $version) {
            if ($errorMessage = $this->validateService($name, $version)) {
                $errors[] = $errorMessage;
            }
        }
        return $errors;
    }

    /**
     * Validates service version whether it is supported by current Magento version or not.
     *
     * @param string $serviceName Service name
     * @param string $version Service version for validation
     * @return string Failed validation message
     *
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    private function validateService($serviceName, $version)
    {
        if (!isset($this->getSupportedVersions()[$serviceName])) {
            return sprintf(
                'Service "%s" is not supported for Magento "%s"',
                $serviceName,
                $this->magentoVersion->getVersion()
            );
        }
        $constraint = $this->getSupportedVersions()[$serviceName];
        if ($version != 'latest' && !Semver::satisfies($version, $this->getSupportedVersions()[$serviceName])) {
            return sprintf(
                'Magento %s does not support version "%s" for service "%s".'
                    . 'Service version should satisfy "%s" constraint.',
                $this->magentoVersion->getVersion(),
                $version,
                $serviceName,
                $constraint
            );
        }

        return '';
    }

    /**
     * Retrieves supported version constraints of services for current Magento version
     *
     * @return array
     *
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    private function getSupportedVersions()
    {
        if (null === $this->supportedVersionList) {
            $magentoVersions = array_keys(self::MAGENTO_SUPPORTED_SERVICE_VERSIONS);
            foreach ($magentoVersions as $constraint) {
                if (Semver::satisfies($this->magentoVersion->getVersion(), $constraint)) {
                    $this->supportedVersionList = self::MAGENTO_SUPPORTED_SERVICE_VERSIONS[$constraint];
                    break;
                }
            }
            if (!$this->supportedVersionList) {
                throw new ConfigurationMismatchException(sprintf(
                    'There are no defined configurations for "%s" Magento version',
                    $this->magentoVersion->getVersion()
                ));
            }
        }
        return $this->supportedVersionList;
    }
}
