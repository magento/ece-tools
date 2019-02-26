<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

use Magento\MagentoCloud\Test\DockerIntegration\Process\Process;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class AbstractTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        (new Process('docker-compose down -v && docker-compose up -d'))
            ->setTimeout(null)
            ->mustRun();

        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        (new Process('docker-compose down -v'))
            ->setTimeout(null)
            ->mustRun();

        parent::tearDown();
    }
}
