<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Service\EolValidator as EOLValidator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Class to validate service EOLs and issue warnings/notices accordingly.
 *
 * Class ServiceEol
 */
class ServiceEol implements ValidatorInterface
{
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
     * @param Validator\ResultFactory $resultFactory
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
     * Get the defined services and versions and check for their EOLs.
     *
     * @return Validator\ResultInterface
     * @throws \Exception
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            $results = [];
            $result = $this->eolValidator->validateServiceEol('php', '7.1');
            if($result) {
                $results = $result;
            }
            if($results) {
                print_r($results);
            }
        } catch (GenericException $e) {
            return $this->resultFactory->error($e->getMessage());
        }

        return $this->resultFactory->success();
    }
}