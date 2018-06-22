<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Checks that configuration from deploy phase is appropriate for current magento version.
 */
class AppropriateVersion implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Variable\ConfigurationChecker
     */
    private $configurationChecker;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param MagentoVersion $magentoVersion
     * @param Variable\ConfigurationChecker $configurationChecker
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        MagentoVersion $magentoVersion,
        Validator\Deploy\Variable\ConfigurationChecker $configurationChecker
    ) {
        $this->resultFactory = $resultFactory;
        $this->magentoVersion = $magentoVersion;
        $this->configurationChecker = $configurationChecker;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $errors = [];

        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            $variables = [
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER,
                DeployInterface::VAR_SCD_STRATEGY,
            ];

            foreach($variables as $variableName) {
                if ($this->configurationChecker->isConfigured($variableName, true)) {
                    $errors[] = sprintf(
                        'The variable %s is allowed from magento version 2.2.0',
                        $variableName
                    );
                }
            }
        }

        if ($this->magentoVersion->satisfies('<2.1.0 || >=2.2.0')
            && $this->configurationChecker->isConfigured(DeployInterface::VAR_GENERATED_CODE_SYMLINK, true)
        ) {
            $errors[] = sprintf(
                'The variable %s is allowed for magento version 2.1.x',
                DeployInterface::VAR_GENERATED_CODE_SYMLINK
            );
        }

        if ($errors) {
            return $this->resultFactory->create(Validator\Result\Error::ERROR, [
                'error' => 'Some configuration is not suitable with current version of magento',
                'suggestion' => implode(PHP_EOL, $errors),
            ]);
        }

        return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
    }
}
