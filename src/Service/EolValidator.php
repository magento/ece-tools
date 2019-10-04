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
     * @var ServiceFactory
     */
    private $serviceFactory;

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
     * @param Validator\ResultFactory $resultFactory
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(
        FileList $fileList,
        Validator\ResultFactory $resultFactory,
        ServiceFactory $serviceFactory
    ) {
        $this->fileList = $fileList;
        $this->resultFactory = $resultFactory;
        $this->serviceFactory = $serviceFactory;
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
            $errors = [];

            // Get all services and their versions for validation.
            $services = [
                ServiceInterface::NAME_RABBITMQ,
                ServiceInterface::NAME_REDIS,
                ServiceInterface::NAME_DB
            ];

            foreach ($services as $serviceName) {
                $service = $this->serviceFactory->create($serviceName);
                $serviceVersion = $service->getVersion();

                $this->service = $serviceName;
                $this->version = $serviceVersion;

                // Validate EOL for each service.
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
        } catch (GenericException $ex) {
            return $this->resultFactory->error('Can\'t validate EOLs of some services: ' . $ex->getMessage());
        }

        return $this->resultFactory->success();
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
            return $v['version'] == $this->version;
        });

        // If there are no configurations found for the current service and version,
        // or if an EOL is not defined or is invalid, return a message with details.
        if (!$versionConfigs || !$versionConfigs[array_key_first($versionConfigs)]['eol']
            || !$eolDate = date_create($versionConfigs[array_key_first($versionConfigs)]['eol'])) {
            return sprintf(
                'Unknown or invalid EOL defined for %s %s',
                $this->service,
                $this->version
            );
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
