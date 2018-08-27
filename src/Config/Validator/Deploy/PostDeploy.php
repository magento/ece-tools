<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Application\HookChecker;
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
     * @var HookChecker
     */
    private $hookChecker;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param HookChecker $hookChecker
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        HookChecker $hookChecker
    ) {
        $this->resultFactory = $resultFactory;
        $this->hookChecker = $hookChecker;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        if (!$this->hookChecker->isPostDeployHookEnabled()) {
            return $this->resultFactory->error(
                'Your application does not have the "post_deploy" hook enabled.',
                'In order to minimize downtime, add the following to ".magento.app.yaml":' . PHP_EOL .
                'hooks:' . PHP_EOL .
                '    post_deploy: |' . PHP_EOL .
                '        php ./vendor/bin/ece-tools post-deploy'
            );
        }

        return $this->resultFactory->success();
    }
}
