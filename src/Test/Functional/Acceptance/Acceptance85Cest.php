<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Robo\Exception\TaskException;
use Codeception\Example;
use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 *
 * 1. Test successful deploy
 * 2. Test content presence
 * 3. Test config dump
 * 4. Test content presence
 *
 * @group php85
 */
class Acceptance85Cest extends AcceptanceCest
{
    /**
     * Data provider for Magento Cloud versions.
     *
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.9-alpha-opensearch3.0';
}
