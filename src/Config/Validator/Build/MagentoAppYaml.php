<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Validates that .magento.app.yaml contains correct configuration:
 * - CONFIG__STORES__DEFAULT__PAYMENT__BRAINTREE__CHANNEL must be absent in .magento.app.yaml env variables
 *   for Magento > 2.4.0
 *
 */
class MagentoAppYaml implements ValidatorInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     *
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
            if ($this->magentoVersion->satisfies('>= 2.4.0')) {
                $appData = $this->environment->getApplication();
                if (isset($appData['variables']['env']['CONFIG__STORES__DEFAULT__PAYMENT__BRAINTREE__CHANNEL'])) {
                    return $this->resultFactory->errorByCode(AppError::BUILD_WRONG_BRAINTREE_VARIABLE);
                }
            }

            return $this->resultFactory->success();
        } catch (\Exception $e) {
            throw new ValidatorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
