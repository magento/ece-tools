<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Yaml\Yaml;
use Magento\MagentoCloud\Config\Validator;

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
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var string
     */
    private $service = '';

    /**
     * @var string
     */
    private $version = '';

    /**
     * @var int
     */
    private $errorLevel;

    /**
     * EolValidator constructor.
     *
     * @param FileList $fileList
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(FileList $fileList,
        Validator\ResultFactory $resultFactory
    ) {
        $this->fileList = $fileList;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Validate the EOL of a given service and version by error level.
     *
     * @param int $errorLevel
     * @return Validator\ResultInterface
     * @throws \Exception
     */
    public function validateServiceEol(int $errorLevel): Validator\ResultInterface
    {
        try {
            $this->errorLevel = $errorLevel;

            // Get all services and their versions for validation.
            $services = $this->getServices();

            $errors = [];

            // Validate EOL for each service.
            foreach ($services as $service) {
                $this->service = array_key_first($service);
                $this->version = $service[$this->service];
                if ($validationResult = $this->validateService()) {
                    $errors[] = $validationResult;
                }
            }

            if ($errors) {
                return $this->resultFactory->error(
                    ($errorLevel === ValidatorInterface::LEVEL_NOTICE ?
                    'Some services are approaching their EOL.' : 'Some services have passed their EOL.'),
                    implode(PHP_EOL, $errors)
                );
            }
        }
        catch (GenericException $ex) {
            return $this->resultFactory->error('Can\'t validate EOLs of some services: ' . $ex->getMessage());
        }

        return $this->resultFactory->success();
    }

    /**
     * Get all services and versions.
     *
     * @return array
     */
    protected function getServices() : array
    {
        $services = [['php' => '7.1'], ['mysql' => '10.1']];
        return $services;
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
        $configDate = $versionConfigs[array_key_first($versionConfigs)]['eol'];
        $eolDate = new \DateTime($configDate);

        // If the EOL is in the past, issue a warning.
        // If the EOL is in the future, but within a three month period, issue a notice.
        $interval = date_diff($eolDate, new \DateTime());

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
        if(file_exists($this->fileList->getServiceEolsConfig() ?? '')) {
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
     * @param int $errorLevel
     * @param \DateInterval $interval
     * @return string
     */
    protected function getServiceEolNotifications(\DateTime $eolDate, \DateInterval $interval) : string
    {
        switch($this->errorLevel) {
            case ValidatorInterface::LEVEL_WARNING:
                // If the EOL date is in the past, issue a warning.
                if ($interval->invert === 0) {
                    return sprintf(
                        '%s %s has passed its EOL (%s).',
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

