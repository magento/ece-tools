<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service\Version;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * Validate Service versions for docker configuration.
 */
class Validator
{
    const MAGENTO_SUPPORTED_SERVICE_VERSIONS = [
        '<2.2.0' => [
            //ServiceFactory::SERVICE_CLI => '~5.6.5 || 7.0.2 || 7.0.4 || ~7.0.6 || ~7.1.0',
            ServiceFactory::SERVICE_FPM => '~5.6.5 || 7.0.2 || 7.0.4 || ~7.0.6 || ~7.1.0',
            ServiceFactory::SERVICE_DB => '>=10.0 <10.3',
            ServiceFactory::SERVICE_NGINX => '^1.0',
            ServiceFactory::SERVICE_VARNISH => '~3.5 || ~4.0',
            //ServiceFactory::SERVICE_TLS => '*',
            ServiceFactory::SERVICE_REDIS => '~3.2 || ~4.0 || ~5.0',
            ServiceFactory::SERVICE_ELASTICSEARCH => '^2.0 || ^5.0 || ^6.0',
            ServiceFactory::SERVICE_RABBIT_MQ => '~3.5',
        ],
        '>=2.2.0 <2.2.8' => [
            //ServiceFactory::SERVICE_CLI => '~7.0.13 || ~7.1',
            ServiceFactory::SERVICE_FPM => '~7.0.13 || ~7.1',
            ServiceFactory::SERVICE_DB => '>=10.0 <10.3',
            ServiceFactory::SERVICE_NGINX => '^1.0',
            ServiceFactory::SERVICE_VARNISH => '~4.0 || ~5.0',
            //ServiceFactory::SERVICE_TLS => '*',
            ServiceFactory::SERVICE_REDIS => '~3.2 || ~4.0 || ~5.0',
            ServiceFactory::SERVICE_ELASTICSEARCH => '^2.0 || ^5.0',
            ServiceFactory::SERVICE_RABBIT_MQ => '~3.5',
        ],
        '>=2.2.8 <2.3.0' => [
            //ServiceFactory::SERVICE_CLI => '~7.0.13 || ~7.1',
            ServiceFactory::SERVICE_FPM => '~7.0.13 || ~7.1',
            ServiceFactory::SERVICE_DB => '>=10.0 <10.3',
            ServiceFactory::SERVICE_NGINX => '^1.0',
            ServiceFactory::SERVICE_VARNISH => '~4.0 || ~5.0',
            //ServiceFactory::SERVICE_TLS => '*',
            ServiceFactory::SERVICE_REDIS => '~3.2 || ~4.0 || ~5.0',
            ServiceFactory::SERVICE_ELASTICSEARCH => '^2.0 || ^5.0 || ^6.0',
            ServiceFactory::SERVICE_RABBIT_MQ => '~3.5',
        ],
        '>=2.3.0' => [
            //ServiceFactory::SERVICE_CLI => '~7.1.3 || ~7.2.0',
            ServiceFactory::SERVICE_FPM => '~7.1.3 || ~7.2.0',
            ServiceFactory::SERVICE_DB => '>=10.0 <10.3',
            ServiceFactory::SERVICE_NGINX => '^1.0',
            ServiceFactory::SERVICE_VARNISH => '~4.0 || ~5.0',
            //ServiceFactory::SERVICE_TLS => '*',
            ServiceFactory::SERVICE_REDIS => '~3.2 || ~4.0 || ~5.0',
            ServiceFactory::SERVICE_ELASTICSEARCH => '^2.0 || ^5.0 || ^6.0',
            ServiceFactory::SERVICE_RABBIT_MQ => '~3.7',
        ]
    ];

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * List of allowed service versions for current Magento version
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
     * @param array $versionList
     * @return array
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    public function validate($versionList)
    {
        $errors = [];
        foreach ($versionList as $name => $version) {
            if ($errorMessage = $this->validateService($name, $version)) {
                $errors[] = $errorMessage;
            }
        }
        return $errors;
    }

    /**
     * @param string $serviceName
     * @param string $version
     * @return string
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    public function validateService($serviceName, $version)
    {
        if (!isset($this->getSupportedVersions()[$serviceName])) {
            throw new ConfigurationMismatchException(sprintf(
                'Service "%s" is not supported for Magento "%s"',
                $serviceName,
                $this->magentoVersion->getVersion()
            ));
        }

        if ($version != 'latest' && !Semver::satisfies($version, $this->getSupportedVersions()[$serviceName])) {
            return sprintf('Magento %s does not support version "%s" for service "%s"',
                $this->magentoVersion->getVersion(),
                $version,
                $serviceName
            );
        }

        return '';
    }

    /**
     * @return array|mixed
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    protected function getSupportedVersions()
    {
        if (null !== $this->supportedVersionList) {
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
