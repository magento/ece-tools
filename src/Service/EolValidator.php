<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

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
     * @var string
     */
    private $service = '';

    /**
     * @var string
     */
    private $version = '';

    /**
     * EolValidator constructor.
     * @param FileList $fileList
     */
    public function __construct(FileList $fileList)
    {
        $this->fileList = $fileList;
    }

    /**
     * Validate the EOL of a given service and version.
     *
     * @param string $service
     * @param string $version
     * @return string
     * @throws \Exception
     */
    public function validateServiceEol(string $service, string $version) : string
    {
        // Set the service and version.
        $this->service = $service;
        $this->version = $version;

        // Get the EOL configurations for the current service.
        $serviceConfigs = $this->getServiceEolConfigs();

        // Check if configurations exist for the current service and version.
        $versionConfigs = array_filter($serviceConfigs, function($v) {
            return $v['version'] == $this->version;
        });

        // If there are no configurations found for the current service and version,
        // return a message with details.
        if (!$versionConfigs) {
            return sprintf(
                'Could not validate EOL for service %s and version %s.',
                $this->service,
                $this->version);
        }

        // Get the EOL date from the configs.
        $eolDate = $versionConfigs[array_key_first($versionConfigs)]['eol'];

        // If the EOL is in the past, issue a warning.
        // If the EOL is in the future, but within a three month period, issue a notice.
        $interval = date_diff(new \DateTime($eolDate), new \DateTime());
        if ($interval->invert === 0) {
            // If the EOL date is in the past, issue a warning.
            return 'Issue warning!';
        } else if ($interval->invert === 1 && $interval->y == 0 && $interval->m <= 3) {
            // If the EOL date is within 3 months in the future, issue a notice.
            return 'Issue notice!';
        }

        return '';
    }

    /**
     * Get the EOL configurations for the current service from eol.yaml.
     *
     * @return array
     */
    protected function getServiceEolConfigs() : array
    {
        // Check if the the configuration yaml file exists, and retrieve it's path.
        if(file_exists($this->fileList->getServiceEolsConfig() ?? '')) {
            $configs = Yaml::parseFile($this->fileList->getServiceEolsConfig() ?? '');
            // Return the configurations for the specific service.
            return $configs[$this->service];
        }

        return array();
    }
}