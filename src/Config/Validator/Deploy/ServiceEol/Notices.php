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
     * @var EOLValidator
     */
    private $eolValidator;

    /**
     * ServiceEol constructor.
     *
     * @param EOLValidator $eolValidator
     */
    public function __construct(EOLValidator $eolValidator)
    {
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
        return $this->eolValidator->validateServiceEol(self::ERROR_LEVEL);
    }
}