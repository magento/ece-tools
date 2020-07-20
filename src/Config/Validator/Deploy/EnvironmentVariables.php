<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Validates that environment variables do not contain redundant variables.
 * - CONFIG__STORES__DEFAULT__PAYMENT__BRAINTREE__CHANNEL must be absent in env variables for Magento >= 2.4.0
 */
class EnvironmentVariables implements ValidatorInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param Environment $environment
     * @param MagentoVersion $magentoVersion
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(
        Environment $environment,
        MagentoVersion $magentoVersion,
        Validator\ResultFactory $resultFactory
    ) {
        $this->environment = $environment;
        $this->magentoVersion = $magentoVersion;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritDoc
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            if ($this->magentoVersion->satisfies('>= 2.4.0')
                && $this->environment->getEnv('CONFIG__STORES__DEFAULT__PAYMENT__BRAINTREE__CHANNEL')
            ) {
                return $this->resultFactory->errorByCode(Error::DEPLOY_WRONG_BRAINTREE_VARIABLE);
            }

            return $this->resultFactory->success();
        } catch (\Exception $e) {
            throw new ValidatorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
