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
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Validates if SPLIT_DB configuration is used for Magento 2.4.2+
 */
class DeprecatedSplitDb implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var SplitDb
     */
    private $splitDb;

    /**
     * @param ResultFactory $resultFactory
     * @param MagentoVersion $magentoVersion
     * @param SplitDb $splitDb
     */
    public function __construct(
        ResultFactory $resultFactory,
        MagentoVersion $magentoVersion,
        SplitDb $splitDb
    ) {
        $this->resultFactory = $resultFactory;
        $this->magentoVersion = $magentoVersion;
        $this->splitDb = $splitDb;
    }

    /**
     * @inheritDoc
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            if ($this->magentoVersion->satisfies('>= 2.4.2 < 2.5.0')
                && $this->splitDb->isConfigured()
            ) {
                return $this->resultFactory->errorByCode(Error::WARN_DEPRECATED_SPLIT_DB);
            }
        } catch (GenericException $e) {
            throw new ValidatorException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->resultFactory->success();
    }
}
