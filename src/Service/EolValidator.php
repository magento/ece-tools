<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Carbon\Carbon;
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
     * Set the notification period.
     */
    private const NOTIFICATION_PERIOD = 3;

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
     * @var array
     */
    private $services = [
        ServiceInterface::NAME_PHP,
        ServiceInterface::NAME_ELASTICSEARCH,
        ServiceInterface::NAME_RABBITMQ,
        ServiceInterface::NAME_REDIS,
        ServiceInterface::NAME_DB
    ];

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
        $errors = [];

        foreach ($this->services as $serviceName) {
            $serviceVersion = $this->getServiceVersion($serviceName);

            // Validate EOL for each service.
            if ($validationResult = $this->validateService($serviceName, $serviceVersion, $errorLevel)) {
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
    private function getServiceVersion(string $serviceName) : string
    {
        switch ($serviceName) {
            case ServiceInterface::NAME_PHP:
                $serviceVersion = PHP_VERSION;
                break;
            case ServiceInterface::NAME_ELASTICSEARCH:
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
     * @param string $serviceName
     * @param string $serviceVersion
     * @param int $errorLevel
     * @return string
     */
    private function validateService(string $serviceName, string $serviceVersion, int $errorLevel) : string
    {
        // Get the EOL configurations for the current service.
        $serviceConfigs = $this->getServiceEolConfigs($serviceName);

        // Check if configurations exist for the current service and version.
        $versionConfigs = array_filter($serviceConfigs, function ($v) use ($serviceVersion) {
            return Semver::satisfies($serviceVersion, sprintf('%s.x', $v['version']));
        });

        // If there are no configurations found for the current service and version,
        // or if an EOL is not defined or is invalid, return a message with details.
        if (!$versionConfigs || $versionConfigs[array_key_first($versionConfigs)]['eol'] === null) {
            return $errorLevel === ValidatorInterface::LEVEL_WARNING ? sprintf(
                'Unknown or invalid EOL defined for %s %s',
                $serviceName,
                $serviceVersion
            ) : '';
        }

        $eolDate = Carbon::createFromTimestamp($versionConfigs[array_key_first($versionConfigs)]['eol']);

        // If the EOL is in the past, issue a warning.
        // If the EOL is in the future, but within a three month period, issue a notice.
        return $this->getServiceEolNotifications($eolDate, $errorLevel, $serviceName, $serviceVersion);
    }

    /**
     * Gets the EOL configurations for the current service from eol.yaml.
     *
     * @param string $serviceName
     * @return array
     */
    private function getServiceEolConfigs(string $serviceName) : array
    {
        // Check if the the configuration yaml file exists, and retrieve it's path.
        if (file_exists($this->fileList->getServiceEolsConfig())) {
            $configs = Yaml::parseFile($this->fileList->getServiceEolsConfig());
            // Return the configurations for the specific service.
            if (array_key_exists($serviceName, $configs)) {
                return $configs[$serviceName];
            }
        }

        return [];
    }

    /**
     * Gets the service notifications by error level.
     *
     * @param Carbon $eolDate
     * @param int $errorLevel
     * @param string $serviceName
     * @param string $version
     * @return string
     */
    private function getServiceEolNotifications(
        Carbon $eolDate,
        int $errorLevel,
        string $serviceName,
        string $version
    ) : string {
        switch ($errorLevel) {
            case ValidatorInterface::LEVEL_WARNING:
                // If the EOL date is in the past, issue a warning.
                if (!$eolDate->isFuture()) {
                    return sprintf(
                        '%s %s has passed EOL (%s).',
                        $serviceName,
                        $version,
                        date_format($eolDate, 'Y-m-d')
                    );
                }
                break;
            case ValidatorInterface::LEVEL_NOTICE:
                // If the EOL date is within 3 months in the future, issue a notice.
                if ($eolDate->isFuture() && $eolDate->diffInMonths(Carbon::now()) <= self::NOTIFICATION_PERIOD) {
                    return sprintf(
                        '%s %s is approaching EOL (%s).',
                        $serviceName,
                        $version,
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
