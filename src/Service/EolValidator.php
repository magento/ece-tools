<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Carbon\Carbon;
use Composer\Semver\Semver;
use Magento\MagentoCloud\App\ContainerException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Service\Detector\DatabaseType;
use Magento\MagentoCloud\Util\YamlNormalizer;
use Symfony\Component\Yaml\Yaml;

/**
 * Service EOL validator.
 *
 * Class EolValidator
 *
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
     * @var File
     */
    private $file;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var array
     */
    private $eolConfigs;

    /**
     * @var DatabaseType
     */
    private $databaseType;

    /**
     * @var YamlNormalizer
     */
    private YamlNormalizer $yamlNormalizer;

    /**
     * EolValidator constructor.
     *
     * @param FileList $fileList
     * @param File $file
     * @param ServiceFactory $serviceFactory
     * @param DatabaseType $databaseType
     * @param YamlNormalizer $yamlNormalizer
     */
    public function __construct(
        FileList $fileList,
        File $file,
        ServiceFactory $serviceFactory,
        DatabaseType $databaseType,
        YamlNormalizer $yamlNormalizer
    ) {
        $this->fileList       = $fileList;
        $this->file           = $file;
        $this->serviceFactory = $serviceFactory;
        $this->databaseType   = $databaseType;
        $this->yamlNormalizer = $yamlNormalizer;
    }

    /**
     * Validate the EOL of a given service and version by error level.
     *
     * @return array
     * @throws FileSystemException
     * @throws ServiceMismatchException
     * @throws ServiceException|ContainerException
     */
    public function validateServiceEol(): array
    {
        $errors = [];

        $services = [
            ServiceInterface::NAME_PHP,
            ServiceInterface::NAME_ELASTICSEARCH,
            ServiceInterface::NAME_OPENSEARCH,
            ServiceInterface::NAME_RABBITMQ,
            ServiceInterface::NAME_REDIS,
            ServiceInterface::NAME_REDIS_SESSION,
            ServiceInterface::NAME_VALKEY,
            ServiceInterface::NAME_VALKEY_SESSION,
            ServiceInterface::NAME_ACTIVEMQ,
            $this->databaseType->getServiceName()
        ];

        foreach ($services as $serviceName) {
            $service = $this->serviceFactory->create($serviceName);
            $serviceVersion = $service->getVersion();
            if ($validationResult = $this->validateService(
                $this->getConvertedServiceName($serviceName),
                $serviceVersion
            )) {
                $errorLevel = current(array_keys($validationResult));
                $errors[$errorLevel][] = $validationResult[$errorLevel];
            }
        }
        return $errors;
    }

    /**
     * Validates a given service and version.
     *
     * @param string $serviceName
     * @param string $serviceVersion
     * @return array
     * @throws FileSystemException
     */
    public function validateService(string $serviceName, string $serviceVersion): array
    {
        $serviceConfigs = $this->getServiceConfigs($serviceName);

        $versionConfigs = array_filter($serviceConfigs, function ($v) use ($serviceVersion) {
            return Semver::satisfies($serviceVersion, sprintf('%s.x', $v['version']));
        });

        if (!isset($versionConfigs[current(array_keys($versionConfigs))]['eol'])) {
            return [];
        }

        $eolDateValue = $versionConfigs[current(array_keys($versionConfigs))]['eol'];
        
        // Handle both timestamp and date string formats
        if (is_numeric($eolDateValue)) {
            $eolDate = Carbon::createFromTimestamp($eolDateValue);
        } else {
            $eolDate = Carbon::createFromFormat('Y-m-d', $eolDateValue);
        }

        if (!$eolDate->isFuture()) {
            return [ValidatorInterface::LEVEL_WARNING => sprintf(
                '%s %s has passed EOL (%s).',
                $serviceName,
                $serviceVersion,
                date_format($eolDate, 'Y-m-d')
            )];
        } elseif ($eolDate->isFuture()
            && $eolDate->diffInMonths(Carbon::now()) <= self::NOTIFICATION_PERIOD
        ) {
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
     * Gets the EOL configurations for the current service from eol.yaml.
     *
     * @param string $serviceName
     * @return array
     * @throws FileSystemException
     */
    private function getServiceConfigs(string $serviceName): array
    {
        if ($this->eolConfigs === null) {
            $this->eolConfigs = [];
            $configsPath = $this->fileList->getServiceEolsConfig();
            if ($this->file->isExists($configsPath)) {
                $parseFlags = 0;
                if (defined(Yaml::class . '::PARSE_CONSTANT')) {
                    $parseFlags |= Yaml::PARSE_CONSTANT;
                }
                if (defined(Yaml::class . '::PARSE_CUSTOM_TAGS')) {
                    $parseFlags |= Yaml::PARSE_CUSTOM_TAGS;
                }

                $this->eolConfigs = (array) Yaml::parse(
                    $this->file->fileGetContents($configsPath),
                    $parseFlags
                );

                $this->eolConfigs = $this->yamlNormalizer->normalize($this->eolConfigs) ?? [];
            }
        }

        return $this->eolConfigs[$serviceName] ?? [];
    }

    /**
     * Perform service name conversions.
     * Explicitly resetting 'mysql' to 'mariadb' for MariaDB validation
     * and 'redis-session' to 'redis' for Redis validation; getting the version
     * and 'valkey-session' to 'valkey' for Valkey validation; getting the version
     * from relationship returns mysql:<version>.
     *
     * @param string $serviceName
     * @return string
     */
    private function getConvertedServiceName(string $serviceName): string
    {
        switch ($serviceName) {
            case ServiceInterface::NAME_DB_MYSQL:
                $serviceName = ServiceInterface::NAME_DB_MARIA;
                break;
            case ServiceInterface::NAME_REDIS_SESSION:
                $serviceName = ServiceInterface::NAME_REDIS;
                break;
            case ServiceInterface::NAME_VALKEY_SESSION:
                $serviceName = ServiceInterface::NAME_VALKEY;
                break;
        }

        return $serviceName;
    }
}
