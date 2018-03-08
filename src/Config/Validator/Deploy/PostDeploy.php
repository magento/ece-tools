<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * @inheritdoc
 */
class PostDeploy implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param Environment $environment
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        Environment $environment
    ) {
        $this->resultFactory = $resultFactory;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $applicationEnv = $this->environment->getApplication();

        if (!isset($applicationEnv['hooks']['post_deploy'])) {
            return $this->resultFactory->create(
                Validator\ResultInterface::ERROR,
                [
                    'error' => 'Your application seems not using \'post_deploy\' hook.',
                ]
            );
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}
