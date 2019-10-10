<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy\ServiceEol;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Service\EolValidator as EOLValidator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\App\GenericException;

/**
 * Class to check if services approaching their EOLs.
 */
class Notices implements ValidatorInterface
{
    /**
     * Define validation level.
     */
    const ERROR_LEVEL = ValidatorInterface::LEVEL_NOTICE;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var EOLValidator
     */
    private $eolValidator;

    /**
     * ServiceEol constructor.
     *
     * @param EOLValidator $eolValidator
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        EOLValidator $eolValidator
    ) {
        $this->resultFactory = $resultFactory;
        $this->eolValidator = $eolValidator;
    }

    /**
     * Get the defined services and versions and check for their EOLs by error level.
     *
     * @return Validator\ResultInterface
     * @throws \Exception
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            $errors = $this->eolValidator->validateServiceEol(self::ERROR_LEVEL);

            if ($errors) {
                return $this->resultFactory->error(
                    'Some services are approaching EOL.',
                    implode(PHP_EOL, $errors)
                );
            }
        } catch (GenericException $e) {
            return $this->resultFactory->error('Can\'t validate version of some services: ' . $e->getMessage());
        }

        return $this->resultFactory->success();
    }
}
