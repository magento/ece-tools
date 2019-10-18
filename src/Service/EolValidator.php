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
     * @return array
     * @throws ServiceMismatchException
     */
    public function validateServiceEol(): array
    {
        $errors = [];

        foreach ($this->services as $serviceName) {
            $serviceVersion = $this->getServiceVersion($serviceName);

            if ($validationResult = $this->validateService($serviceName, $serviceVersion)) {
                $key = array_key_first($validationResult);
                $errors[$key][] = $validationResult[$key];
            }
        }
        return $errors;
    }

    /**
     * Validates a given service and version.
     * @param string $serviceName
     * @param string $serviceVersion
     * @return array
     */
    public function validateService(string $serviceName, string $serviceVersion) : array
    {
        $serviceConfigs = $this->getServiceEolConfigs($serviceName);

        $versionConfigs = array_filter($serviceConfigs, function ($v) use ($serviceVersion) {
            return Semver::satisfies($serviceVersion, sprintf('%s.x', $v['version']));
        });

        if (!$versionConfigs || $versionConfigs[array_key_first($versionConfigs)]['eol'] === null) {
            return [ValidatorInterface::LEVEL_WARNING => sprintf(
                'Unknown or invalid EOL defined for %s %s',
                $serviceName,
                $serviceVersion
            )];
        }

        $eolDate = Carbon::createFromTimestamp($versionConfigs[array_key_first($versionConfigs)]['eol']);

        if (!$eolDate->isFuture()) {
            return [ValidatorInterface::LEVEL_WARNING => sprintf(
                '%s %s has passed EOL (%s).',
                $serviceName,
                $serviceVersion,
                date_format($eolDate, 'Y-m-d')
            )];
        } else if ($eolDate->isFuture()
        && $eolDate->diffInMonths(Carbon::now()) <= self::NOTIFICATION_PERIOD) {
            return [ValidatorInterface::LEVEL_NOTICE => sprintf(
                '%s %s is approaching EOL (%s).',
                $serviceName,
                $serviceVersion,
                date_format($eolDate, 'Y-m-d')
            )];
        }

        return [];
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
     * Gets the EOL configurations for the current service from eol.yaml.
     * @param string $serviceName
     * @return array
     */
    private function getServiceEolConfigs(string $serviceName) : array
    {
        if (file_exists($this->fileList->getServiceEolsConfig())) {
            $configs = Yaml::parseFile($this->fileList->getServiceEolsConfig());
            // Return the configurations for the specific service.
            if (array_key_exists($serviceName, $configs)) {
                return $configs[$serviceName];
            }
        }

        return [];
    }
}
