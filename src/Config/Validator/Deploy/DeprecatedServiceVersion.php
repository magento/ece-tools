<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\Validator as ServiceVersionValidator;
use Psr\Log\LoggerInterface;

/**
 * Validates on service version deprecation.
 */
class DeprecatedServiceVersion implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param ServiceFactory $serviceFactory
     * @param MagentoVersion $magentoVersion
     * @param LoggerInterface $logger
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        ServiceFactory $serviceFactory,
        MagentoVersion $magentoVersion,
        LoggerInterface $logger
    ) {
        $this->resultFactory = $resultFactory;
        $this->serviceFactory = $serviceFactory;
        $this->magentoVersion = $magentoVersion;
        $this->logger = $logger;
    }

    /**
     * Validates on service version deprecation.
     * Add warning log message in case of Exception
     *
     * @see ServiceVersionValidator::MAGENTO_DEPRECATED_SERVICE_VERSIONS
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $errors = [];

        try {
            foreach (ServiceVersionValidator::MAGENTO_DEPRECATED_SERVICE_VERSIONS as $serviceName => $constraints) {
                $service = $this->serviceFactory->create($serviceName);
                $serviceVersion = $service->getVersion();
                if ($serviceVersion === '0') {
                    continue;
                }

                if ($this->isServiceVersionDeprecated($serviceVersion, $constraints)) {
                    $errors[] = sprintf(
                        'Service "%s:%s" is deprecated for Magento "%s"',
                        $serviceName,
                        $serviceVersion,
                        $this->magentoVersion->getVersion()
                    );
                }
            }

            if ($errors) {
                return $this->resultFactory->error(
                    'Some of installed services is deprecated for current Magento version:',
                    implode(PHP_EOL, $errors)
                );
            }
        } catch (\Exception $e) {
            $this->logger->warning('Can\'t validate services on deprecation: ' . $e->getMessage());
        }


        return $this->resultFactory->success();
    }

    /**
     * @param string $serviceVersion
     * @param array $constraints
     * @return bool
     * @throws UndefinedPackageException
     */
    private function isServiceVersionDeprecated(string $serviceVersion, array $constraints): bool
    {
        foreach ($constraints as $magentoConstraint => $serviceConstraint) {
            if (Semver::satisfies($this->magentoVersion->getVersion(), $magentoConstraint)
                && Semver::satisfies($serviceVersion, $serviceConstraint)
            ) {
                return true;
            }
        }

        return false;
    }
}
