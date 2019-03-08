<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

use Magento\MagentoCloud\Test\DockerIntegration\Process;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
abstract class AbstractTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        (new Process\EnvUp())
            ->setTimeout(60)
            ->mustRun();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        (new Process\EnvDown())
            ->setTimeout(60)
            ->mustRun();
    }
}
