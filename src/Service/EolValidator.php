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
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

/**
 * Service EOL validator.
 *
 * Class EolValidator
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
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
     * @param FileList $fileList
     * @param File $file
     * @param ServiceFactory $serviceFactory
     * @param DatabaseType $databaseType
     */
    public function __construct(
        FileList $fileList,
        File $file,
        ServiceFactory $serviceFactory,
        DatabaseType $databaseType
    ) {
        $this->fileList = $fileList;
        $this->file = $file;
        $this->serviceFactory = $serviceFactory;
        $this->databaseType = $databaseType;
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
    public function validateService(string $serviceName, string $serviceVersion) : array
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
    private function getServiceConfigs(string $serviceName) : array
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

                $this->eolConfigs = $this->normalizeYamlData($this->eolConfigs) ?? [];
            }
        }

        return $this->eolConfigs[$serviceName] ?? [];
    }

    /**
     * Recursively normalizes Symfony YAML TaggedValue objects into PHP-native values.
     *
     * Handles the following YAML tags:
     *  - !env: resolves environment variables.
     *  - !include: parses and normalizes included YAML files.
     *  - !php/const: resolves PHP constants (e.g. !php/const:\PDO::ATTR_ERRMODE).
     *  - Other or unknown tags: recursively normalize their values.
     *
     * Ensures all YAML data is converted to scalars or arrays suitable for safe merging.
     *
     * @param mixed $data The parsed YAML data (array, scalar, or TaggedValue).
     * @return mixed The normalized data structure.
     *
     * @SuppressWarnings("PHPMD.NPathComplexity")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity") Method is intentionally complex due to tag resolution logic.
     */
    private function normalizeYamlData(mixed $data): mixed
    {
        if ($data instanceof TaggedValue) {
            $tag   = $data->getTag();   // e.g. "php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE"
            $value = $data->getValue();

            // Handle php/const tags (Symfony strips leading '!')
            if (str_starts_with($tag, 'php/const:')) {
                // Extract the constant name
                $constName = substr($tag, strlen('php/const:'));
                $constName = ltrim($constName, '\\');

                // Resolve the constant name to its value if defined
                $constKey = defined($constName) ? constant($constName) : $constName;

                // Handle YAML quirk where ": 1" is parsed literally
                $raw = is_string($value) ? $value : (string)$value;
                $cleanVal = str_replace([':', ' '], '', $raw);
                $constVal = is_numeric($cleanVal) ? (int)$cleanVal : $cleanVal;

                return [$constKey => $constVal];
            }

            // Handle !env
            if ($tag === 'env') {
                $envValue = getenv((string)$value);
                return $envValue !== false ? $envValue : null;
            }

            // Handle !include
            if ($tag === 'include') {
                if (file_exists((string)$value)) {
                    $included = Yaml::parseFile((string)$value);
                    return $this->normalizeYamlData($included);
                }
                return null;
            }

            // Default — recursively normalize nested tagged structures
            $normalized = $this->normalizeYamlData($value);
            return is_array($normalized) ? $normalized : [$normalized];
        }

        // Recursively normalize arrays
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalizeYamlData($value);
            }
        }

        return $data;
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
    private function getConvertedServiceName(string $serviceName) : string
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
