<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\SearchEngine\ElasticSearch;
use Magento\MagentoCloud\Service\Service;
use Magento\MagentoCloud\Service\Validator as ServiceVersionValidator;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

class ServiceVersion implements ValidatorInterface
{
    /**
     * @var ServiceVersionValidator
     */
    private $serviceVersionValidator;
    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @param ServiceVersionValidator $serviceVersionValidator
     */
    public function __construct(
        ServiceVersionValidator $serviceVersionValidator,
        ElasticSearch $elasticSearch
    ) {
        $this->serviceVersionValidator = $serviceVersionValidator;
        $this->elasticSearch = $elasticSearch;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $services = [
            Service::NAME_PHP => PHP_VERSION
        ];

        if ($this->elasticSearch->getVersion()) {
            $services[Service::NAME_REDIS] = $this->elasticSearch->getVersion();
        }

        if ($redisInstalled) {
            $services[Service::NAME_RABBITMQ] = $this->elasticSearch->getVersion();
        }
            Service::NAME_REDIS => $this->elasticSearch->getVersion(),
            Service::NAME_RABBITMQ,
            Service::NAME_ELASTICSEARCH,
        ];

        $errors = [];
        foreach ($services as $name => $version) {
            $errors = $this->validateService($name, $version);
            if ($error = $this->serviceVersionValidator->validateService($name, $version)) {
                $errors[] = $error;
            }
        }
    }

    /**
     * @param string $name
     * @param string $version
     * @return string
     */
    private function validateService(string $name, string $version): string
    {
        try {

        } catch (Con)
    }
}
