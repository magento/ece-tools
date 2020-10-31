<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Service\EolValidator as EOLValidator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Class to check if services approaching their EOLs.
 */
class ServiceEol implements ValidatorInterface
{
    /**
     * @var integer
     */
    private $errorLevel;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var EOLValidator
     */
    private $eolValidator;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param EOLValidator $eolValidator
     * @param int $errorLevel
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        EOLValidator $eolValidator,
        int $errorLevel
    ) {
        $this->resultFactory = $resultFactory;
        $this->eolValidator = $eolValidator;
        $this->errorLevel = $errorLevel;
    }

    /**
     * Get the defined services and versions and check for their EOLs by error level.
     *
     * {@inheritDoc}
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            $errors = $this->eolValidator->validateServiceEol();

            if (isset($errors[$this->errorLevel])) {
                $message = $this->errorLevel == ValidatorInterface::LEVEL_WARNING ?
                    'Some services have passed EOL.' :
                    'Some services are approaching EOL.';
                return $this->resultFactory->error(
                    $message,
                    implode(PHP_EOL, $errors[$this->errorLevel]),
                    $this->errorLevel == ValidatorInterface::LEVEL_WARNING ? Error::WARN_SERVICE_PASSED_EOL : null
                );
            }
        } catch (GenericException $e) {
            return $this->resultFactory->error('Can\'t validate version of some services: ' . $e->getMessage());
        }

        return $this->resultFactory->success();
    }
}
