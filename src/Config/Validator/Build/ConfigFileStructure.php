<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;
use Magento\MagentoCloud\Util\ArrayManager;

/**
 * Validates that configuration file contains enough data for running static content deploy in build phase.
 *
 * For magento version 2.1.x scopes configuration should exists in app/etc/config.local.php.
 * For version 2.2 and higher in app/etc/config.php.
 */
class ConfigFileStructure implements ValidatorInterface
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var SharedConfig
     */
    private $configResolver;

    /**
     * @param ArrayManager $arrayManager
     * @param File $file
     * @param Validator\ResultFactory $resultFactory
     * @param SharedConfig $configResolver
     */
    public function __construct(
        ArrayManager $arrayManager,
        File $file,
        Validator\ResultFactory $resultFactory,
        SharedConfig $configResolver
    ) {
        $this->arrayManager = $arrayManager;
        $this->file = $file;
        $this->resultFactory = $resultFactory;
        $this->configResolver = $configResolver;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $configFile = $this->configResolver->resolve();
        $configFileName = basename($configFile);
        $config = $this->file->isExists($configFile) ? $this->file->requireFile($configFile) : [];

        $flattenedConfig = $this->arrayManager->flatten($config);
        $websites = $this->arrayManager->filter($flattenedConfig, 'scopes/websites', false);
        $stores = $this->arrayManager->filter($flattenedConfig, 'scopes/stores', false);

        if (count($stores) === 0 && count($websites) === 0) {
            $error = 'No stores/website/locales found in ' . $configFileName;
            $suggestion = implode(
                PHP_EOL,
                [
                    'To speed up the deploy process do the following:',
                    '1. Using SSH, log in to your Magento Cloud account',
                    '2. Run "php ./vendor/bin/ece-tools config:dump"',
                    '3. Using SCP, copy the app/etc/%s file to your local repository',
                    '4. Add, commit, and push your changes to the app/etc/%s file',
                ]
            );
            $suggestion = sprintf($suggestion, $configFileName, $configFileName);

            return $this->resultFactory->create(
                Validator\ResultInterface::ERROR,
                [
                    'error' => $error,
                    'suggestion' => $suggestion,
                ]
            );
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}
