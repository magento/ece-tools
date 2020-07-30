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
 * @group php73
 * @group edition-ce
 */
class AcceptanceCe73Cest extends AcceptanceCeCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.3.5';
}
