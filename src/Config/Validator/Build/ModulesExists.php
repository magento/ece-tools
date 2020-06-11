<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Magento\Shared\ReaderInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Verifies of 'modules' section exists in configuration file.
 */
class ModulesExists implements ValidatorInterface
{
    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param ReaderInterface $reader
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(ReaderInterface $reader, Validator\ResultFactory $resultFactory)
    {
        $this->reader = $reader;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        return isset($this->reader->read()['modules'])
            ? $this->resultFactory->success()
            : $this->resultFactory->error(
                'The modules section is missing from the shared config file.',
                '',
                Error::WARN_MISSED_MODULE_SECTION
            );
    }
}
