<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate(): Validator\ResultInterface
    {
        $errors = [];

        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            $variables = [
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER,
                DeployInterface::VAR_SCD_STRATEGY,
                DeployInterface::VAR_SCD_MAX_EXEC_TIME,
            ];

            foreach ($variables as $variableName) {
                if ($this->configurationChecker->isConfigured($variableName, true)) {
                    $errors[] = sprintf(
                        '%s is available for Magento 2.2.0 and later.',
                        $variableName
                    );
                }
            }
        }

        if (!$this->magentoVersion->satisfies('2.1.*')
            && $this->configurationChecker->isConfigured(DeployInterface::VAR_GENERATED_CODE_SYMLINK, true)
        ) {
            $errors[] = sprintf(
                '%s is available for Magento 2.1.x.',
                DeployInterface::VAR_GENERATED_CODE_SYMLINK
            );
        }

        if (!$this->magentoVersion->isGreaterOrEqual('2.4.7')) {
            $variables = [
                DeployInterface::VAR_USE_LUA,
                DeployInterface::VAR_LUA_KEY,
            ];

            foreach ($variables as $variableName) {
                if ($this->configurationChecker->isConfigured($variableName, true)) {
                    $errors[] = sprintf(
                        '%s is available for Magento 2.4.7 and later.',
                        $variableName
                    );
                }
            }
        }

        if ($errors) {
            return $this->resultFactory->error(
                'The current configuration is not compatible with this version of Magento',
                implode(PHP_EOL, $errors),
                Error::WARN_CONFIG_NOT_COMPATIBLE
            );
        }

        return $this->resultFactory->success();
    }
}
