<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Adds sandbox project installation and cleanup abstract features.
 */
abstract class AbstractTest extends TestCase
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::create();
        $this->bootstrap->execute(sprintf(
            'cd %s && composer install -n --no-dev --no-progress',
            $this->bootstrap->getSandboxDir()
        ));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento setup:uninstall -n',
            $this->bootstrap->getSandboxDir()
        ));
        $this->bootstrap->execute(sprintf(
            'cd %s && rm -rf vendor/*',
            $this->bootstrap->getSandboxDir()
        ));
    }
}
