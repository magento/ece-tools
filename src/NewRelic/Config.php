<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\NewRelic;

use Magento\MagentoCloud\Config\Environment;

/**
 * Returns NewRelic configuration
 */
class Config
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     *
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Return new relic api key.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return (string)ini_get('newrelic.license');
    }

    /**
     * Returns NewRelic app name
     *
     * @return string
     */
    public function getAppName(): string
    {
        return (string)ini_get('newrelic.appname');
    }

    /**
     * Returns current revision
     *
     * @return string
     */
    public function getRevision(): string
    {
        return $this->environment->getEnv('MAGENTO_CLOUD_TREE_ID');
    }
}
