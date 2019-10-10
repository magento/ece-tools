<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Yaml\Yaml;

/**
 * Service EOL validator.
 *
 * Class EolValidator
 */
class EolValidator
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $version;

    /**
     * @var integer
     */
    private $errorLevel;

    /**
     * EolValidator constructor.
     *
     * @param FileList $fileList
     * @param ServiceFactory $serviceFactory
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(
        FileList $fileList,
        ServiceFactory $serviceFactory,
        ElasticSearch $elasticSearch
    ) {
        $this->fileList = $fileList;
        $this->serviceFactory = $serviceFactory;
        $this->elasticSearch = $elasticSearch;
    }

    /**
     * Validate the EOL of a given service and version by error level.
     *
     * @param int $errorLevel
     * @return array
     * @throws ServiceMismatchException
     */
    public function validateServiceEol(int $errorLevel): array
    {
        $this->errorLevel = $errorLevel;
        $errors = [];

        // Get all services and their versions for validation.
        $services = [
            ServiceInterface::NAME_PHP,
            ServiceInterface::NAME_ELASTICSEARCH,
            ServiceInterface::NAME_RABBITMQ,
            ServiceInterface::NAME_REDIS,
            ServiceInterface::NAME_DB
        ];

        foreach ($services as $serviceName) {
            $serviceVersion = $this->getServiceVersion($serviceName);

            $this->service = $serviceName;
            $this->version = $serviceVersion;

            // Validate EOL for each service.
            if ($validationResult = $this->validateService()) {
                $errors[] = $validationResult;
            }
        }

        return $errors;
    }

    /**
     * Gets the version of a given service.
     *
     * @param string $serviceName
     * @return string
     * @throws ServiceMismatchException
     */
    protected function getServiceVersion(string $serviceName) : string
    {
        switch ($serviceName) {
            case 'php':
                $serviceVersion = PHP_VERSION;
                break;
            case 'elasticsearch':
                $serviceVersion = $this->elasticSearch->getVersion();
                break;
            default:
                $service = $this->serviceFactory->create($serviceName);
                $serviceVersion = $service->getVersion();
                break;
        }

        return $serviceVersion;
    }

    /**
     * Validates a given service and version.
     *
     * @return string
     * @throws \Exception
     */
    protected function validateService() : string
    {
        // Get the EOL configurations for the current service.
        $serviceConfigs = $this->getServiceEolConfigs();

        // Check if configurations exist for the current service and version.
        $versionConfigs = array_filter($serviceConfigs, function ($v) {
            return Semver::satisfies($this->version, sprintf('%s.x', $v['version']));
        });

        // If there are no configurations found for the current service and version,
        // or if an EOL is not defined or is invalid, return a message with details.
        if (!$versionConfigs || !$versionConfigs[array_key_first($versionConfigs)]['eol']
            || !$eolDate = date_create($versionConfigs[array_key_first($versionConfigs)]['eol'])) {
            return $this->errorLevel === ValidatorInterface::LEVEL_WARNING ? sprintf(
                'Unknown or invalid EOL defined for %s %s',
                $this->service,
                $this->version
            ) : '';
        }

        $interval = date_diff($eolDate, date_create('now'));

        // If the EOL is in the past, issue a warning.
        // If the EOL is in the future, but within a three month period, issue a notice.
        return $this->getServiceEolNotifications($eolDate, $interval);
    }

    /**
     * Gets the EOL configurations for the current service from eol.yaml.
     *
     * @return array
     */
    protected function getServiceEolConfigs() : array
    {
        // Check if the the configuration yaml file exists, and retrieve it's path.
        if (file_exists($this->fileList->getServiceEolsConfig() ?? '')) {
            $configs = Yaml::parseFile($this->fileList->getServiceEolsConfig() ?? '');
            // Return the configurations for the specific service.
            return $configs[$this->service];
        }

        return array();
    }

    /**
     * Gets the service notifications by error level.
     *
     * @param \DateTime $eolDate
     * @param \DateInterval $interval
     * @return string
     */
    protected function getServiceEolNotifications(\DateTime $eolDate, \DateInterval $interval) : string
    {
        switch ($this->errorLevel) {
            case ValidatorInterface::LEVEL_WARNING:
                // If the EOL date is in the past, issue a warning.
                if ($interval->invert === 0) {
                    return sprintf(
                        '%s %s has passed EOL (%s).',
                        $this->service,
                        $this->version,
                        date_format($eolDate, 'Y-m-d')
                    );
                }
                break;
            case ValidatorInterface::LEVEL_NOTICE:
                // If the EOL date is within 3 months in the future, issue a notice.
                if ($interval->invert === 1 && $interval->y == 0 && $interval->m <= 3) {
                    return sprintf(
                        '%s %s is approaching EOL (%s).',
                        $this->service,
                        $this->version,
                        date_format($eolDate, 'Y-m-d')
                    );
                }
                break;
            default:
                break;
        }

        return '';
    }
}
