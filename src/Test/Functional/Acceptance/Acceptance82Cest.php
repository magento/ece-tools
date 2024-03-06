<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Functional\Acceptance;

use CliTester;
use Magento\MagentoCloud\Test\Functional\Acceptance\AcceptanceCest;
use Robo\Exception\TaskException;
use Codeception\Example;
use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * @inheritDoc
 *
 * @group php82
 */
class Acceptance82Cest extends AcceptanceCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.6';
}
