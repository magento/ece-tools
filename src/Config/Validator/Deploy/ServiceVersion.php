<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

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
     * @return Validator\ResultInterface
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     * @throws \Magento\MagentoCloud\Service\ConfigurationMismatchException
     */
    public function validate(): Validator\ResultInterface
    {
        $services = [
            ServiceInterface::NAME_RABBITMQ,
            ServiceInterface::NAME_REDIS
        ];

        $errors = [];
        foreach ($services as $serviceName) {
            $service = $this->serviceFactory->create($serviceName);
            if ($error = $this->serviceVersionValidator->validateService($serviceName, $service->getVersion())) {
                $errors[] = $error;
            }
        }

        if ($errors) {
            return $this->resultFactory->error(
                'The current configuration is not compatible with this version of Magento',
                implode(PHP_EOL, $errors)
            );
        }

        return $this->resultFactory->success();
    }
}
