<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\Validator as ServiceVersionValidator;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates installed service versions according to version mapping.
 * @see \Magento\MagentoCloud\Service\Validator::MAGENTO_SUPPORTED_SERVICE_VERSIONS
 */
class ServiceVersion implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var ServiceVersionValidator
     */
    private $serviceVersionValidator;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param ServiceVersionValidator $serviceVersionValidator
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        ServiceVersionValidator $serviceVersionValidator,
        ServiceFactory $serviceFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->serviceVersionValidator = $serviceVersionValidator;
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * Validates compatibility Redis and RabbitMq services with installed Magento version.
     *
     * {@inheritdoc}
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            $services = [
                ServiceInterface::NAME_RABBITMQ,
                ServiceInterface::NAME_REDIS,
                ServiceInterface::NAME_DB
            ];

            $errors = [];
            foreach ($services as $serviceName) {
                $service = $this->serviceFactory->create($serviceName);
                $serviceVersion = $service->getVersion();
                if ($serviceVersion !== '0' &&
                    $error = $this->serviceVersionValidator->validateService($serviceName, $serviceVersion)
                ) {
                    $errors[] = $error;
                }
            }

            if ($errors) {
                return $this->resultFactory->error(
                    'The current configuration is not compatible with this version of Magento',
                    implode(PHP_EOL, $errors)
                );
            }
        } catch (GenericException $e) {
            return $this->resultFactory->error('Can\'t validate version of some services: ' . $e->getMessage());
        }

        return $this->resultFactory->success();
    }
}
