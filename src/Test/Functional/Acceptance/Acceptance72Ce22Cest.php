<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;
use Robo\Exception\TaskException;
use CliTester;

/**
 * @inheritDoc
 *
 * @group php72
 * @group edition-ce
 */
class Acceptance72Ce22Cest extends AcceptanceCeCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.2.10';
}
