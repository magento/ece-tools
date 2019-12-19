<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Magento\Shared\Resolver;
use Magento\MagentoCloud\Package\UndefinedPackageException;
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
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @param ArrayManager $arrayManager
     * @param Validator\ResultFactory $resultFactory
     * @param Resolver $resolver
     */
    public function __construct(
        ArrayManager $arrayManager,
        Validator\ResultFactory $resultFactory,
        Resolver $resolver
    ) {
        $this->arrayManager = $arrayManager;
        $this->resultFactory = $resultFactory;
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UndefinedPackageException
     */
    public function validate(): Validator\ResultInterface
    {
        $configFile = $this->resolver->getPath();
        $config = $this->resolver->read();

        $configFileName = basename($configFile);

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
